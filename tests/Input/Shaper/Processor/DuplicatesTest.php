<?php

namespace Maketok\DataMigration\Input\Shaper\Processor;

use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DuplicatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param UnitBagInterface $bag
     * @return Duplicates
     */
    public function getShaper(UnitBagInterface $bag)
    {
        return new Duplicates(
            $bag,
            new ArrayMap(),
            new LanguageAdapter(new ExpressionLanguage())
        );
    }

    public function testOneLevelParse()
    {
        $unit = new Unit('test');
        $bag = new SimpleBag();
        $bag->add($unit);

        $rows = [
            ['code' => 'test', 'id' => 1, 'name' => 'bar'],
            ['code' => 'test2', 'id' => 11, 'name' => 'baz'],
        ];

        $shaper = $this->getShaper($bag);

        foreach ($rows as $row) {
            $this->assertSame([$row], $shaper->parse($row));
        }
    }

    public function testTwoLevelParse()
    {
        $unit1 = new Unit('customer');
        $unit1->setIsEntityCondition(function ($map) {
            return !empty($map['email']);
        });
        $unit2 = new Unit('address');
        $unit2->setParent($unit1);

        $unit3 = new Unit('address_data');
        $unit3->addSibling($unit2);

        $bag = new SimpleBag();
        $bag->addSet([$unit1, $unit2, $unit3]);

        $entities = [
            [
                'email' => 'bob@example.com',
                'name' => 'bob',
                'address' => [
                    [
                        'street' => 'charity str.',
                    ],
                ]
            ],
            [
                'email' => 'paul@example.com',
                'name' => 'paul',
                'address' => [
                    [
                        'street' => 'buckingham ave.',
                    ],
                    [
                        'street' => 'mirabelle str.',
                    ],
                ]
            ],
        ];
        $expected = [
            [
                ['email' => 'bob@example.com', 'name' => 'bob', 'street' => 'charity str.'],
            ],
            [
                ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'buckingham ave.'],
                ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'mirabelle str.'],
            ]
        ];

        $shaper = $this->getShaper($bag);

        for ($i = 0; $i<count($entities); $i++) {
            $this->assertSame($expected[$i], $shaper->parse($entities[$i]));
        }
    }

    public function testTwoLevelParse2()
    {
        $unit1 = new Unit('customer');
        $unit1->setIsEntityCondition(function ($map) {
            return !empty($map['email']);
        });
        $unit2 = new Unit('address');
        $unit2->setParent($unit1);

        $bag = new SimpleBag();
        $bag->addSet([$unit1, $unit2]);

        $entities = [
            [
                'email' => 'bob@example.com',
                'name' => 'bob',
                'address' => [
                    [
                        'addr_name' => 'billy',
                        'street' => 'charity str.',
                    ],
                ]
            ],
            [
                'email' => 'paul@example.com',
                'name' => 'paul',
                'address' => [
                    [
                        'addr_name' => 'paul',
                        'street' => 'buckingham ave.',
                    ],
                    [
                        'addr_name' => 'megan',
                        'street' => 'mirabelle str.',
                    ],
                    [
                        'addr_name' => 'tiffany',
                        'street' => 'mirabelle str.',
                    ],
                ]
            ],
        ];
        $expected = [
            [
                ['email' => 'bob@example.com', 'name' => 'bob', 'addr_name' => 'billy', 'street' => 'charity str.'],
            ],
            [
                ['email' => 'paul@example.com', 'name' => 'paul', 'addr_name' => 'paul', 'street' => 'buckingham ave.'],
                ['email' => 'paul@example.com', 'name' => 'paul', 'addr_name' => 'megan', 'street' => 'mirabelle str.'],
                ['email' => 'paul@example.com', 'name' => 'paul', 'addr_name' => 'tiffany', 'street' => 'mirabelle str.'],
            ]
        ];

        $shaper = $this->getShaper($bag);

        for ($i = 0; $i<count($entities); $i++) {
            $this->assertSame($expected[$i], $shaper->parse($entities[$i]));
        }
    }

    public function testThreeLevelParse()
    {
        $unit1 = new Unit('customer');
        $unit1->setIsEntityCondition(function ($map) {
            return !empty($map['email']);
        });
        $unit2 = new Unit('address');
        $unit2->setParent($unit1);
        $unit2->setIsEntityCondition(function ($map, $oldmap) {
            return $map['street'] != $oldmap['street'];
        });

        $unit3 = new Unit('address_data');
        $unit3->setParent($unit2);

        $bag = new SimpleBag();
        $bag->addSet([$unit1, $unit2, $unit3]);

        $entities = [
            [
                'email' => 'bob@example.com',
                'name' => 'bob',
                'address' => [
                    [
                        'street' => 'charity str.',
                        'address_data' => [
                            [
                                'phone' => '123',
                            ],
                            [
                                'phone' => '432',
                            ]
                        ],
                    ],
                ]
            ],
            [
                'email' => 'paul@example.com',
                'name' => 'paul',
                'address' => [
                    [
                        'street' => 'buckingham ave.',
                        'address_data' => [
                            [
                                'phone' => '222',
                            ]
                        ],
                    ],
                    [
                        'street' => 'mirabelle str.',
                        'address_data' => [
                            [
                                'phone' => '323',
                            ]
                        ],
                    ],
                ]
            ],
        ];
        $expected = [
            [
                ['email' => 'bob@example.com', 'name' => 'bob', 'street' => 'charity str.', 'phone' => '123'],
                ['email' => 'bob@example.com', 'name' => 'bob', 'street' => 'charity str.', 'phone' => '432'],
            ],
            [
                ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'buckingham ave.', 'phone' => '222'],
                ['email' => 'paul@example.com', 'name' => 'paul', 'street' => 'mirabelle str.', 'phone' => '323'],
            ]
        ];

        $shaper = $this->getShaper($bag);

        for ($i = 0; $i<count($entities); $i++) {
            $this->assertSame($expected[$i], $shaper->parse($entities[$i]));
        }
    }
}
