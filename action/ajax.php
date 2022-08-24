<?

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

$result = [];

function addCoupon($discountId, $userId){

    $result = [];

    CModule::IncludeModule('catalog');

    $COUPON = CatalogGenerateCoupon();

    $res = \Bitrix\Sale\Internals\DiscountCouponTable::add(
        array(
            'DISCOUNT_ID' => $discountId,
            'COUPON'      => $COUPON,
            'TYPE'        => \Bitrix\Sale\Internals\DiscountCouponTable::TYPE_BASKET_ROW,
            'MAX_USE'     => 1,
            'USER_ID'     => $userId,
            'DESCRIPTION' => ''
        )
    );

    $result = [
        'res' => $res,
        'COUPON' => $COUPON
    ];

    return $result;
}

switch ($_REQUEST['OPTION']){
    case 'GET_ACTION':

        if(
            !empty($userId = $_REQUEST['USER_ID'])
        ) {

            global $APPLICATION;
            $arItemIds = [36, 37, 38, 40];//id товаров на которые будут распростряняться скидки
            CModule::IncludeModule('sale');

            $arFilter = [
                'USER_ID' => $userId,
                ">=DATE_CREATE" => date("d.m.Y H:i:s", time() - 60 * 60)
            ];

            $arSelect = ['DISCOUNT_ID', 'COUPON'];

            $res = \Bitrix\Sale\Internals\DiscountCouponTable::getList(
                [
                    'filter' => $arFilter,
                    'select' => $arSelect
                ]
            );

            if($arr = $res->Fetch()){//если такой купон найден

                $result['COUPON'] = $arr['COUPON'];

                $arFilter = [
                    'ID' => $arr['DISCOUNT_ID']
                ];

                $arSelect = [
                    'ID',
                    "NAME"
                ];

                //найти название скидки этого купона
                $res2 = \Bitrix\Sale\Internals\DiscountTable::getList(
                    [
                        'filter' => $arFilter,
                        'select' => $arSelect
                    ]
                );

                if($arr2 = $res2->Fetch()){
                    $result['DISCOUNT_ID'] = $arr2['ID'];
                    $result['DISCOUNT_NAME'] = $arr2['NAME'];
                }

            }
            else{//купон не найден

                //генерируем случайное число от 1 до 50

                $number = rand(1, 50);

                //пытаемся найти скидку с этим числом скидки

                $arrFilter = [
                    'NAME' => "Скидка ".$number."%"//не ищу по DISCOUNT_VALUE так как
                    // DISCOUNT_VALUE - величина скидки. Устаревшее поле с 14.0, значение жестко переопределяются при добавлении;
                ];

                $arrSelect = [
                    'ID',
                    'NAME'
                ];

                $res = \Bitrix\Sale\Internals\DiscountTable::getList(
                    [
                        'filter' => $arrFilter,
                        'select' => $arrSelect
                    ]
                );

                if($arr = $res->Fetch()){

                    $discountId = $arr['ID'];

                    $result['DISCOUNT_ID'] = $discountId;
                    $result['DISCOUNT_NAME'] = $arr['NAME'];

                    $resultAddCoupon = addCoupon($discountId, $userId);

                    if (!empty($resultAddCoupon['res'])) {
                        $result['COUPON'] = $resultAddCoupon['COUPON'];
                    } else {
                        $result['COUPON'] = '';
                        $result['ERROR_COUPON_CREATE'] = $APPLICATION->GetException();
                    }
                }
                else {

                    //если не нашли генерируем новую скидку если нужно и выписываем купон

                    $arDiscountFields = [
                        "LID" => SITE_ID,
                        "SITE_ID" => SITE_ID,
                        "NAME" => "Скидка " . $number . "%",
                        "DISCOUNT_VALUE" => $number,
                        "DISCOUNT_TYPE" => "P",
                        "LAST_LEVEL_DISCOUNT" => "Y",
                        "LAST_DISCOUNT" => "Y",
                        "ACTIVE" => "Y",
                        "CURRENCY" => "RUB",
                        "USER_GROUPS" => [2],
                        'ACTIONS' => [
                            "CLASS_ID" => "CondGroup",
                            "DATA" => [
                                "All" => "AND"
                            ],
                            "CHILDREN" => [
                                [
                                    "CLASS_ID" => "ActSaleBsktGrp",
                                    "DATA" => [
                                        "Type" => "Discount",
                                        "Value" => $number,
                                        "Unit" => "Perc",
                                        "Max" => 0,
                                        "All" => "OR",
                                        "True" => "True",
                                    ],
                                    "CHILDREN" => [
                                        [
                                            "CLASS_ID" => "ActSaleSubGrp",
                                            "DATA" => [
                                                "All" => "AND",
                                                "True" => "True",
                                            ],
                                            "CHILDREN" => [
                                                [
                                                    "CLASS_ID" => "CondIBElement",
                                                    "DATA" => [
                                                        "logic" => "Equal",
                                                        "value" => $arItemIds,
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "CONDITIONS" => [
                            'CLASS_ID' => 'CondGroup',
                            'DATA' => [
                                'All' => 'AND',
                                'True' => 'True',
                            ],
                            'CHILDREN' => [
                                [
                                    "CLASS_ID" => "CondBsktProductGroup",
                                    "DATA" => [
                                        "Found" => "Found",
                                        "All" => "OR",
                                    ],
                                    "CHILDREN" => [
                                        [
                                            "CLASS_ID" => "CondIBElement",
                                            "DATA" => [
                                                "logic" => "Equal",
                                                "value" => $arItemIds,
                                            ]
                                        ]
                                    ]
                                ],
                            ],
                        ]
                    ];

                    $res = CSaleDiscount::Add(
                        $arDiscountFields
                    );

                    if (!empty($res)) {//res - id созданной скидки

                        $result['DISCOUNT_ID'] = $res;
                        $result['DISCOUNT_NAME'] = "Скидка " . $number . "%";
                        //выписываем по ней купон для этого пользователя

                        $resultAddCoupon = addCoupon($res, $userId);

                        if (!empty($resultAddCoupon['res'])) {
                            $result['COUPON'] = $resultAddCoupon['COUPON'];
                        } else {
                            $result['COUPON'] = '';
                            $result['ERROR_COUPON_CREATE'] = $APPLICATION->GetException();
                        }
                    }
                }
            }

        }
        break;

    case 'CHECK_ACTION':

        if(
            !empty($userId = $_REQUEST['USER_ID'])
            && !empty($coupon = $_REQUEST['COUPON'])
        ) {
            CModule::IncludeModule('sale');

            $arFilter = [
                'COUPON' => $coupon
            ];

            $res = \Bitrix\Sale\Internals\DiscountCouponTable::getList(
                [
                    'filter' => $arFilter,
                ]
            );

            if ($arr = $res->Fetch()) {

                if (
                    $arr['USER_ID'] != $userId//другой пользователь владелец купона
                    || time() - $arr['DATE_CREATE']->getTimestamp() > 3 * 60 * 60
                ){
                    //уведомление "Скидка не доступна"
                    $result['res'] = 'discount not available';
                }
                else{
                    //отображаем название скидки

                    $arrFilter = [
                        'ID' => $arr['DISCOUNT_ID']
                    ];

                    $arrSelect = [
                        'ID',
                        'NAME'
                    ];

                    $res2 = \Bitrix\Sale\Internals\DiscountTable::getList(
                        [
                            'filter' => $arrFilter,
                            'select' => $arrSelect
                        ]
                    );

                    if($arr2 = $res2->Fetch()){
                        $result['DISCOUNT_ID'] = $arr2['ID'];
                        $result['DISCOUNT_NAME'] = $arr2['NAME'];
                    }
                }
            } else {
                $result['res'] = 'coupon not found';
            }
        }

        break;
}

echo json_encode($result);
