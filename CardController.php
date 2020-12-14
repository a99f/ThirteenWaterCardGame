<?php
    /**
     * Copyright (C) 2020 A99F.COM Inc. All rights reserved.
     * This is source code from a99f-card-game-controller.
     * The distribution of any copyright must be permitted by A99F.COM.
     * 此代码归A99F(A99F.com)版权所有.
     * 概要说明:
     * 设计UI文档地址：
     * 产品文档地址：
     * 关联API地址 ：
     * 讨论文档地址：
     * 需求说明
     * 安全性说明：
     * 功能性说明：
     * 性能要求；
     * 输入参数：
     * 输出参数：
     * 数据库操作说明：
     * 日期: Created by liyu on 3:32 下午.
     * 作者: A99F
     * 更新版本          日期         作者              备注
     * v0001            2020/12/14     A99F            完成文件创建
     * 规划TODO-LIST：
     * 清单编号          预计日期         作者             状态               备注
     * td0001           0000/00/00      A99F          实现/未实现/进行中
     */

    require_once __DIR__ . '/../Dao/Redis.class.php';
    require_once __DIR__ . '/../Model/Room.class.php';

    global $redis, $room;
    //redis类
    $redis = new \Card\Dao\Redis();
    //房间类
    $room = new \Card\Model\Room();
    /**
     * 发牌生成器
     *
     * @param int $player 玩家人数
     *
     * @return string 发牌信息
     */
    function randCardNumber($player = 4) {
        $cardNumber = range(1, 52);
        //shuffle 将数组顺序随即打乱
        shuffle($cardNumber);
        //array_slice 取该数组中的某一段
        $num = 13;
        //从随机的牌中抽取数额
        $cardGroup1 = array_slice($cardNumber, 0, $num);
        // print_r($result);

        $leftCardGroup = [];
        //循环所有的牌
        foreach ($cardNumber as $key => $item) {
            $isExistSameCard = false;
            //如果这些牌里已经被抽出去,那么要在原牌的基础上抽出这些牌
            //循环已经抽出的牌
            foreach ($cardGroup1 as $key1 => $item1) {
                //如果第一组牌里的牌在原牌面里存在
                if ($item1 == $item) {
                    $isExistSameCard = true;
                }
            }
            //退出比对后如果标识符还是为false,那么说明是新牌(剩下的牌)
            if ($isExistSameCard == false) {
                array_push($leftCardGroup, $item);
            }
        }

        //shuffle 将数组顺序随即打乱
        shuffle($leftCardGroup);
        $cardGroup2 = array_slice($leftCardGroup, 0, $num);

        $returnCardGroup = [];
        sort($cardGroup1);
        $returnCardGroup["group1"] = $cardGroup1;
        sort($cardGroup2);
        $returnCardGroup["group2"] = $cardGroup2;

        //三人基础上发牌
        if ($player >= 3 && $player <= 4) {
            $leftCardGroup2 = [];
            //循环所有的牌
            foreach ($leftCardGroup as $key => $item) {
                $isExistSameCard = false;
                //如果这些牌里已经被抽出去,那么要在原牌的基础上抽出这些牌
                //循环已经抽出的牌
                foreach ($cardGroup2 as $key1 => $item1) {
                    //如果第一组牌里的牌在原牌面里存在
                    if ($item1 == $item) {
                        $isExistSameCard = true;
                    }
                }

                if ($isExistSameCard == false) {
                    array_push($leftCardGroup2, $item);
                }
            }
            //从随机的牌中抽取数额
            $cardGroup3 = array_slice($leftCardGroup2, 0, $num);
            //牌面按从大到小排列
            sort($cardGroup3);
            $returnCardGroup["group3"] = $cardGroup3;

            if ($player == 4) {
                $leftCardGroup3 = [];
                //循环所有的牌
                foreach ($leftCardGroup2 as $key3 => $item3) {
                    $isExistSameCard = false;
                    //如果这些牌里已经被抽出去,那么要在原牌的基础上抽出这些牌
                    //循环已经抽出的牌
                    foreach ($cardGroup3 as $key2 => $item2) {
                        //如果第一组牌里的牌在原牌面里存在
                        if ($item2 == $item3) {
                            $isExistSameCard = true;
                        }
                    }

                    if ($isExistSameCard == false) {
                        array_push($leftCardGroup3, $item3);
                    }
                }

                //从随机的牌中抽取数额
                $cardGroup4 = array_slice($leftCardGroup3, 0, $num);
                //牌面按从大到小排列
                sort($cardGroup4);
                $returnCardGroup["group4"] = $cardGroup4;
            }

        }

        // return json_encode($result);
        return $returnCardGroup;
    }

    /**
     * 从十三牌中选取5张牌
     *
     * @param $cardArray
     *
     * @return array
     */
    function getFiveCardFromThirteen($cardArray) {
        $cardArrayCombination = getCombination($cardArray, 5);
        return $cardArrayCombination;
    }

    /**
     * 牌型整理
     *
     * @param $cardArrayGroup
     *
     * @return array
     */
    function cardTypeArrangement($cardArrayGroup) {
        //参数准备区域
        $cardType                                 = [];
        $cardType["straightFlush"]["num"]         = 0;
        $cardType["straightFlush"]["card"]        = [];
        $cardType["fourSameWithOneDiff"]["num"]   = 0;
        $cardType["fourSameWithOneDiff"]["card"]  = [];
        $cardType["threeSameWithPair"]["num"]     = 0;
        $cardType["threeSameWithPair"]["card"]    = [];
        $cardType["fiveSameSuit"]["num"]          = 0;
        $cardType["fiveSameSuit"]["card"]         = [];
        $cardType["straight"]["num"]              = 0;
        $cardType["straight"]["card"]             = [];
        $cardType["threeSameWithTwoDiff"]["num"]  = 0;
        $cardType["threeSameWithTwoDiff"]["card"] = [];
        $cardType["twoPairWithOneDiff"]["num"]    = 0;
        $cardType["twoPairWithOneDiff"]["card"]   = [];
        $cardType["onePairWithThreeDiff"]["num"]  = 0;
        $cardType["onePairWithThreeDiff"]["card"] = [];
        $cardType["fiveDiff"]["num"]              = 0;
        $cardType["fiveDiff"]["card"]             = [];

        //每一组牌的遍历
        foreach ($cardArrayGroup as $key => $item) {
            //将每组牌的字符串拆分成数组
            $item = explode(",", $item);
            // $returnPlayer1["combination"][$key]["card"] = $item;
            $isSpecialCard = isSpecialInFiveCard($item);
            /**
             * straightFlush
             * fourSameWithOneDiff
             * threeSameWithPair
             * fiveSameSuit
             * straight
             * threeSameWithTwoDiff
             * twoPairWithOneDiff
             * onePairWithThreeDiff
             * fiveDiff
             */
            switch ($isSpecialCard) {
                case "straightFlush":
                    $cardType["straightFlush"]["num"] = $cardType["straightFlush"]["num"] + 1;
                    array_push($cardType["straightFlush"]["card"], $item);
                    break;
                case "fourSameWithOneDiff":
                    $cardType["fourSameWithOneDiff"]["num"] = $cardType["fourSameWithOneDiff"]["num"] + 1;
                    array_push($cardType["fourSameWithOneDiff"]["card"], $item);
                    break;
                case "threeSameWithPair":
                    $cardType["threeSameWithPair"]["num"] = $cardType["threeSameWithPair"]["num"] + 1;
                    array_push($cardType["threeSameWithPair"]["card"], $item);
                    break;
                case "fiveSameSuit":
                    $cardType["fiveSameSuit"]["num"] = $cardType["fiveSameSuit"]["num"] + 1;
                    array_push($cardType["fiveSameSuit"]["card"], $item);
                    break;
                case "straight":
                    $cardType["straight"]["num"] = $cardType["straight"]["num"] + 1;
                    array_push($cardType["straight"]["card"], $item);
                    break;
                case "threeSameWithTwoDiff":
                    $cardType["threeSameWithTwoDiff"]["num"] = $cardType["threeSameWithTwoDiff"]["num"] + 1;
                    array_push($cardType["threeSameWithTwoDiff"]["card"], $item);
                    break;
                case "twoPairWithOneDiff":
                    $cardType["straightFlush"]["num"] = $cardType["straightFlush"]["num"] + 1;
                    array_push($cardType["twoPairWithOneDiff"]["card"], $item);
                    break;
                case "onePairWithThreeDiff":
                    $cardType["onePairWithThreeDiff"]["num"] = $cardType["onePairWithThreeDiff"]["num"] + 1;
                    array_push($cardType["onePairWithThreeDiff"]["card"], $item);
                    break;
                case "fiveDiff":
                    $cardType["fiveDiff"]["num"] = $cardType["fiveDiff"]["num"] + 1;
                    array_push($cardType["fiveDiff"]["card"], $item);
                    break;
                default:
                    break;
            }
        }

        //定制牌型
        // $cardType["straightFlush"]["num"]   = [];
        // $cardType["straightFlush"]["cards"] = [];
        return $cardType;
    }

    /**
     * 针对新的需求新的牌型整理
     * 移除了乌龙的组合
     * 移除了对子的全类型组合
     * 移除了三条的全类型组合
     *
     * @param $cardArrayCombinationGroup
     *
     * @return array
     */
    function cardTypeArrangementFix000($cardArrayCombinationGroup, $cardArray) {
        $cardType                                 = [];
        $cardType["straightFlush"]["num"]         = 0;
        $cardType["straightFlush"]["card"]        = [];
        $cardType["fourSameWithOneDiff"]["num"]   = 0;
        $cardType["fourSameWithOneDiff"]["card"]  = [];
        $cardType["threeSameWithPair"]["num"]     = 0;
        $cardType["threeSameWithPair"]["card"]    = [];
        $cardType["fiveSameSuit"]["num"]          = 0;
        $cardType["fiveSameSuit"]["card"]         = [];
        $cardType["straight"]["num"]              = 0;
        $cardType["straight"]["card"]             = [];
        $cardType["threeSameWithTwoDiff"]["num"]  = 0;
        $cardType["threeSameWithTwoDiff"]["card"] = [];
        $cardType["twoPairWithOneDiff"]["num"]    = 0;
        $cardType["twoPairWithOneDiff"]["card"]   = [];
        $cardType["onePairWithThreeDiff"]["num"]  = 0;
        $cardType["onePairWithThreeDiff"]["card"] = [];
        // $cardType["fiveDiff"]["num"]              = 0;
        // $cardType["fiveDiff"]["card"]             = [];

        //每一组牌的遍历
        //这里遍历的牌型组合是13取5
        // $count = 0;
        foreach ($cardArrayCombinationGroup as $key => $item1) {
            //将每组牌的字符串拆分成数组
            $item = explode(",", $item1);
            // rsort($item);
            // $returnPlayer1["combination"][$key]["card"] = $item;
            $isSpecialCard = isSpecialInFiveCard($item);
            // $count         = $count + 1;
            /**
             * straightFlush
             * fourSameWithOneDiff
             * threeSameWithPair
             * fiveSameSuit
             * straight
             * threeSameWithTwoDiff
             * twoPairWithOneDiff
             * onePairWithThreeDiff
             * fiveDiff
             */
            switch ($isSpecialCard) {
                case "straightFlush": //同花顺
                    $cardType["straightFlush"]["num"] = $cardType["straightFlush"]["num"] + 1;
                    array_push($cardType["straightFlush"]["card"], $item);
                    break;
                // case "fourSameWithOneDiff": //铁支
                //     $cardType["fourSameWithOneDiff"]["num"] = $cardType["fourSameWithOneDiff"]["num"] + 1;
                //     array_push($cardType["fourSameWithOneDiff"]["card"], $item);
                //     break;
                case "threeSameWithPair": //葫芦
                    $cardType["threeSameWithPair"]["num"] = $cardType["threeSameWithPair"]["num"] + 1;
                    array_push($cardType["threeSameWithPair"]["card"], $item);
                    break;
                case "fiveSameSuit": //同花
                    $cardType["fiveSameSuit"]["num"] = $cardType["fiveSameSuit"]["num"] + 1;
                    array_push($cardType["fiveSameSuit"]["card"], $item);
                    break;
                case "straight": //顺子
                    $cardType["straight"]["num"] = $cardType["straight"]["num"] + 1;
                    array_push($cardType["straight"]["card"], $item);
                    break;
                // case "threeSameWithTwoDiff": //三条
                //     $cardType["threeSameWithTwoDiff"]["num"] = $cardType["threeSameWithTwoDiff"]["num"] + 1;
                //     array_push($cardType["threeSameWithTwoDiff"]["card"], $item);
                //     break;
                // case "twoPairWithOneDiff": //两对
                //     $cardType["twoPairWithOneDiff"]["num"] = $cardType["twoPairWithOneDiff"]["num"] + 1;
                //     array_push($cardType["twoPairWithOneDiff"]["card"], $item);
                //     break;
                // case "onePairWithThreeDiff": //对子
                //     $cardType["onePairWithThreeDiff"]["num"] = $cardType["onePairWithThreeDiff"]["num"] + 1;
                //     array_push($cardType["onePairWithThreeDiff"]["card"], $item);
                // break;
                // case "fiveDiff":
                //     $cardType["fiveDiff"]["num"] = $cardType["fiveDiff"]["num"] + 1;
                //     array_push($cardType["fiveDiff"]["card"], $item);
                // break;
                default:
                    break;
            }

            // echo "count----->" . $count;
        }

        /**
         * 在全部统计完牌型后,针对某些牌型进行过滤和筛选
         * 三条,对子,只需要发送其中明显特征的牌的排列组合就可以了
         * 而它们都只需要对牌面进行选择,而不需要牌值
         */

        //针对对子的精简
        $getOnePairWithThreeDiffLiteArray         = getOnePairWithThreeDiffLiteArray($cardArray);
        $cardType["onePairWithThreeDiff"]["num"]  = count($getOnePairWithThreeDiffLiteArray);
        $cardType["onePairWithThreeDiff"]["card"] = $getOnePairWithThreeDiffLiteArray;
        // array_push($cardType["onePairWithThreeDiff"]["card"], $item);

        //针对两对的精简
        $getTwoPairWithOneDiffLiteArray         = getTwoPairWithOneDiffLiteArray($cardArray);
        $cardType["twoPairWithOneDiff"]["num"]  = count($getTwoPairWithOneDiffLiteArray);
        $cardType["twoPairWithOneDiff"]["card"] = $getTwoPairWithOneDiffLiteArray;

        //针对三条的精简
        $getThreeSameCardWithTwoDiffLiteArray     = getThreeSameCardWithTwoDiffLiteArray($cardArray);
        $cardType["threeSameWithTwoDiff"]["num"]  = count($getThreeSameCardWithTwoDiffLiteArray);
        $cardType["threeSameWithTwoDiff"]["card"] = $getThreeSameCardWithTwoDiffLiteArray;

        //针对铁支的精简
        $getFourSameWithOneDiffLiteArray         = getFourSameWithOneDiffLiteArray($cardArray);
        $cardType["fourSameWithOneDiff"]["num"]  = count($getFourSameWithOneDiffLiteArray);
        $cardType["fourSameWithOneDiff"]["card"] = $getFourSameWithOneDiffLiteArray;

        //定制牌型
        // $cardType["straightFlush"]["num"]   = [];
        // $cardType["straightFlush"]["cards"] = [];
        return $cardType;
    }

    /**
     * 返回铁支的精简组合
     */
    function getFourSameWithOneDiffLiteArray($cardArray) {
        // $cardArray = randCardNumber(2);
        // $array     = $cardArray["group1"];
        //测试两对
        // $array = [1, 2, 3, 4, 19, 24, 31, 35, 36, 48, 47, 46, 45];
        rsort($cardArray);

        $cardCombination = getCombination($cardArray, 4);

        $flag = "false";
        //牌型如[1,2,2] [2,1,2],[2,2,1]
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $fourSameArray = [];

        foreach ($cardCombination as $combinationIndex => $combinationString) { //[A,B,C]
            $cardFourArray     = explode(",", $combinationString);
            $cardPositionArray = [];
            //查询每一个数字在数组中的组
            foreach ($cardFourArray as $cardThreeArrayIndex => $oneCard) {
                //循环全数组
                foreach ($fullArray as $fullArrayIndex => $oneFullArrayCard) {
                    if (in_array($oneCard, $oneFullArrayCard)) { //如果在全数组中
                        // echo "数组中第" . $cardFourArrayIndex . "个数字确定在全数组中第" . $fullArrayIndex . "组" . "<br/>";
                        $cardPositionArray[$oneCard] = $fullArrayIndex;
                    }
                }
            }

            //判断是否是三条
            $samePositionArray = array_count_values($cardPositionArray);

            if (count($samePositionArray) == 1) { //在同一组中,可以判断为三条
                // echo "<pre>";
                // print_r($cardPositionArray);
                // print_r($cardFourArray);
                array_push($fourSameArray, $cardFourArray);
            }

            // echo "-----------------------------------------------------<br/>";
        }

        // echo "<pre>";
        // print_r($fourSameArray);

        return $fourSameArray;
    }

    /**
     * 返回对子的精简组合
     */
    function getOnePairWithThreeDiffLiteArray($cardArray) {
        rsort($cardArray);
        $cardCombination = getCombination($cardArray, 2);
        // echo "<pre>";
        // var_dump($cardCombination);
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray        = [];
        $cardPairArray            = [];
        $lastPairInFullArrayIndex = 0;
        //循环排列组合
        foreach ($cardCombination as $cardTwoArrayIndex => $cardTwoString) {
            //将字符串拆分为数组,这个数组是两张牌的排列组合数组,由上一层循环提供
            $cardArrayTwo = explode(",", $cardTwoString);
            //理解为数组循环中的上一张牌
            $tempCardIndex = 0;
            $isCardPair    = false;

            //查询每一张牌在全数组中的位置
            foreach ($cardArrayTwo as $cardTwoInnerIndex => $cardTwoInnerNumber) { //[A,B]
                //遍历全数组中的牌
                foreach ($fullArray as $fullArrayIndex => $fullArrayNumber) { //[A,B,C,D]
                    //比对牌在全数组中的位置
                    if (in_array($cardTwoInnerNumber, $fullArrayNumber)) { //确定在数组中

                        // echo "数组中第" . $cardTwoInnerIndex . "个数字确定在全数组中第" . $fullArrayIndex . "组" . "<br/>";
                        if ($tempCardIndex !== 0) {
                            //和上一张牌的位置比对
                            if ($tempCardIndex === $fullArrayIndex) {
                                //相同,可以确定这一整个数组是需要的对子
                                //筛选出来放入一个新的数组中
                                $isCardPair = true;
                            }
                        }

                        $tempCardIndex            = $fullArrayIndex;
                        $lastPairInFullArrayIndex = $fullArrayIndex;
                        // $cardPositionArray[$item] = $key1;
                        // array_push($cardPositionArray[$item2], $key1);
                        // Position[A,B]=[位置1,位置2];
                        //if 位置1 === 位置2 then 判断为对子

                    } // is CardTwoInnerNumber in FullArray END
                }// [A,B,C,D] END
            } //[A,B] END

            if ($isCardPair) { //如果存在对子
                // $cardPairArray[$lastPairInFullArrayIndex] = [];
                if (!isset($cardPairArray[$lastPairInFullArrayIndex])) {
                    $cardPairArray[$lastPairInFullArrayIndex] = [];
                }
                array_push($cardPairArray[$lastPairInFullArrayIndex], $cardArrayTwo);
            }
            // echo "-----------------------------------------------------<br/>";
        }

        //过滤掉相同的牌组
        //如果存在[A,B]多组存在于[A,B,C,D]中
        //那么只取权重最高的一组,数组位置0的元素
        $returnCardArray = [];
        foreach ($cardPairArray as $key => $item) {
            array_push($returnCardArray, $item[0]);
        }
        // echo "<pre>";
        // print_r($cardPairArray);
        // print_r($returnCardArray);
        return $returnCardArray;
    }

    /**
     * 返回两对的精简
     */
    function getTwoPairWithOneDiffLiteArray($cardArray) {
        rsort($cardArray);
        // $array = $cardArray["group1"];
        $cardCombination = getCombination($cardArray, 4);
        // echo "count:" . count($array) . "<br/>";
        // echo "<pre>";
        // var_dump($array);
        // isTwoPairWithOneDiff([44, 43, 31, 26, 25]); //212
        // isTwoPairWithOneDiff([44, 43, 38, 37, 31]); //221
        // isTwoPairWithOneDiff([52, 44, 43, 26, 25]); //122
        // $flag = isTwoPairWithOneDiff([1, 17, 19, 35, 3]); //221
        // foreach ($array as $item) {
        //     $item1 = explode(",", $item);
        //     $flag  = isSpecialInFiveCard($item1); //221
        //     echo "flag:" . $flag . "<br/>";
        // }

        $flag = "false";
        //牌型如[1,2,2] [2,1,2],[2,2,1]
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $pairArray = [];

        foreach ($cardCombination as $combinationIndex => $combinationString) { //[A,B,C,D]
            $cardFourArray     = explode(",", $combinationString);
            $cardPositionArray = [];
            //查询每一个数字在数组中的组
            foreach ($cardFourArray as $cardFourArrayIndex => $oneCard) {
                //循环全数组
                foreach ($fullArray as $fullArrayIndex => $oneFullArrayCard) {
                    if (in_array($oneCard, $oneFullArrayCard)) { //如果在全数组中
                        // echo "数组中第" . $cardFourArrayIndex . "个数字确定在全数组中第" . $fullArrayIndex . "组" . "<br/>";
                        $cardPositionArray[$oneCard] = $fullArrayIndex;
                    }
                }
            }

            //判断是否是两对
            $samePositionArray = array_count_values($cardPositionArray);

            if (count($samePositionArray) == 2) {
                // echo "是两对" . "<br/>";
                if (current($samePositionArray) === 2 && end($samePositionArray) === 2) {
                    // echo "<pre>";
                    // print_r($cardPositionArray);
                    // print_r($cardFourArray);
                    //此时已经拿到对子了
                    //再对pair进行精简,如果前一组和后一组在一个位置上,那么就放进同一个对子序列中
                    //组成一个新的键名,这个键名的组合是 第一个对子在全数组中的序列 + 第二个对子在全数组中的序列
                    //形如 "1-3" 表示全数组第一个对子在第一序列,第二个对子在第三序列
                    $pairArrayKeyName = $cardPositionArray[$cardFourArray[0]] . "-" . $cardPositionArray[$cardFourArray[2]];
                    if (!isset($pairArray[$pairArrayKeyName])) {
                        $pairArray[$pairArrayKeyName] = [];
                    }

                    array_push($pairArray[$pairArrayKeyName], $cardFourArray);
                }
            }

            // echo "-----------------------------------------------------<br/>";
        }

        //这里的pairArray形式如下:
        //     Array
        //     (
        //         [0-1] => Array
        //         (
        //             [0] => Array
        //                    (
        //                        [0] => 52
        //                     [1] => 49
        //                     [2] => 48
        //                     [3] => 45
        //                 )
        //
        //         )
        //
        //     [0-2] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 52
        //                     [1] => 49
        //                     [2] => 44
        //                     [3] => 42
        //                 )
        //
        //         )
        //
        //     [0-3] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 52
        //                     [1] => 49
        //                     [2] => 40
        //                     [3] => 39
        //                 )
        //
        //         )
        //
        //     [0-4] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 52
        //                     [1] => 49
        //                     [2] => 35
        //                     [3] => 33
        //                 )
        //
        //         )
        //
        //     [0-7] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 52
        //                     [1] => 49
        //                     [2] => 24
        //                     [3] => 22
        //                 )
        //
        //         )
        //
        //     [1-2] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 48
        //                     [1] => 45
        //                     [2] => 44
        //                     [3] => 42
        //                 )
        //
        //         )
        //
        //     [1-3] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 48
        //                     [1] => 45
        //                     [2] => 40
        //                     [3] => 39
        //                 )
        //
        //         )
        //
        //     [1-4] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 48
        //                     [1] => 45
        //                     [2] => 35
        //                     [3] => 33
        //                 )
        //
        //         )
        //
        //     [1-7] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 48
        //                     [1] => 45
        //                     [2] => 24
        //                     [3] => 22
        //                 )
        //
        //         )
        //
        //     [2-3] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 44
        //                     [1] => 42
        //                     [2] => 40
        //                     [3] => 39
        //                 )
        //
        //         )
        //
        //     [2-4] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 44
        //                     [1] => 42
        //                     [2] => 35
        //                     [3] => 33
        //                 )
        //
        //         )
        //
        //     [2-7] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 44
        //                     [1] => 42
        //                     [2] => 24
        //                     [3] => 22
        //                 )
        //
        //         )
        //
        //     [3-4] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 40
        //                     [1] => 39
        //                     [2] => 35
        //                     [3] => 33
        //                 )
        //
        //         )
        //
        //     [3-7] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 40
        //                     [1] => 39
        //                     [2] => 24
        //                     [3] => 22
        //                 )
        //
        //         )
        //
        //     [4-7] => Array
        // (
        //     [0] => Array
        //            (
        //                [0] => 35
        //                     [1] => 33
        //                     [2] => 24
        //                     [3] => 22
        //                 )
        //
        //         )
        //
        // )
        // print_r($pairArray);

        //这里只取$pairArray数组的首个元素返回
        $returnArray = [];
        foreach ($pairArray as $item) {
            array_push($returnArray, $item[0]);
        }
        // echo "<pre>";
        // print_r($returnArray);

        return $returnArray;
    }

    /**
     * 对三条数组精简
     *
     * @param $cardArray
     *
     * @return mixed
     */
    function getThreeSameCardWithTwoDiffLiteArray($cardArray) {
        rsort($cardArray);

        $cardCombination = getCombination($cardArray, 3);

        $flag = "false";
        //牌型如[1,2,2] [2,1,2],[2,2,1]
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $threeSameArray = [];

        foreach ($cardCombination as $combinationIndex => $combinationString) { //[A,B,C]
            $cardThreeArray    = explode(",", $combinationString);
            $cardPositionArray = [];
            //查询每一个数字在数组中的组
            foreach ($cardThreeArray as $cardThreeArrayIndex => $oneCard) {
                //循环全数组
                foreach ($fullArray as $fullArrayIndex => $oneFullArrayCard) {
                    if (in_array($oneCard, $oneFullArrayCard)) { //如果在全数组中
                        // echo "数组中第" . $cardFourArrayIndex . "个数字确定在全数组中第" . $fullArrayIndex . "组" . "<br/>";
                        $cardPositionArray[$oneCard] = $fullArrayIndex;
                    }
                }
            }

            //判断是否是三条
            $samePositionArray = array_count_values($cardPositionArray);

            if (count($samePositionArray) == 1) { //在同一组中,可以判断为三条
                // echo "<pre>";
                // print_r($cardPositionArray);
                // print_r($cardFourArray);
                array_push($threeSameArray, $cardThreeArray);
            }

            // echo "-----------------------------------------------------<br/>";
        }

        // echo "<pre>";
        // print_r($threeSameArray);
        return $threeSameArray;
    }

    /**
     * 对牌的排列组合
     *
     * @param $cardArray
     * @param $length
     *
     * @return array
     */
    function getCombination($cardArray, $length) {
        $result = [];
        if (!empty($cardArray)) {
            //如果长度为1,只需要返回数组
            if ($length == 1) {
                return $cardArray;
            }
            //如果长度等于数组长度,那么返回整数组
            if ($length == count($cardArray)) {
                $result[] = implode(',', $cardArray);
                return $result;
            }

            //抽出第一张牌
            $temp_firstelement = $cardArray[0];
            //从数组中删除第一张牌
            unset($cardArray[0]);

            $cardArray  = array_values($cardArray);
            $temp_list1 = getCombination($cardArray, ($length - 1));

            foreach ($temp_list1 as $s) {
                $s        = $temp_firstelement . ',' . $s;
                $result[] = $s;
            }
            unset($temp_list1);

            $temp_list2 = getCombination($cardArray, $length);
            foreach ($temp_list2 as $s) {
                $result[] = $s;
            }
            unset($temp_list2);
        } else {
            echo "ERROR: getCombination--->cardArray is empty\n";
        }

        return $result;
    }

    /**
     * 判断是否是特殊牌型
     * =============================================================================================================
     * 名称    英文                         中文                      数值
     * =============================================================================================================
     * 五同    fiveSameCardValue            wutong
     * 同花顺  straightFlush                tonghuashun
     * 铁支    fourSameWithOneDiff          tiezhi
     * 葫芦    threeSameWithPair            hulu
     * 同花    fiveSameSuit                 tonghua
     * 顺子    straight                     shunzi
     * 三条    threeSameWithTwoDiff         santiao
     * 两对    twoPairWithOneDiff           liangdui
     * 对子    onePairWithThreeDiff         duizi
     * 乌龙    fiveDiff                     wulong
     * =============================================================================================================
     * 任意传进来的13张牌,按3 5 5形式分隔
     * 对5张牌形式判断是否存在特殊牌型
     */

    /**
     * 判断五张牌是否是特殊牌
     * 移除了乌龙的组合
     * 移除了对子的全类型组合
     * 移除了三条的全类型组合
     * 针对特殊牌型组合进行了精简
     *
     * @param $cardArray
     *
     * @return string
     */
    function isSpecialInFiveCard($cardArray) {
        //["true","tonghua"]
        //["false",""]
        rsort($cardArray);
        $cardType = "";
        // $isTwoPairWithOneDiff = isTwoPairWithOneDiff($cardArray);
        // echo "是否是两对:" . $isTwoPairWithOneDiff . " " . json_encode($cardArray) . " ";

        $isFiveCardStraightFlush = isFiveCardStraightFlush($cardArray); //判断是否是同花顺
        if ($isFiveCardStraightFlush === "true") { //同花顺
            $cardType = "straightFlush";
        } else {
            $isTie = isTie($cardArray);
            if ($isTie === "true") { //铁支
                $cardType = "fourSameWithOneDiff";
            } else {
                $isFullThreeSameWithPair = isFullThreeSameWithPair($cardArray);
                if ($isFullThreeSameWithPair === "true") { //葫芦
                    $cardType = "threeSameWithPair";
                } else {
                    $isFullSuit = isFullSuit($cardArray);
                    if ($isFullSuit === "true") { //同花
                        $cardType = "fiveSameSuit";
                    } else {
                        $isStraight = isStraight($cardArray);
                        if ($isStraight === "true") { //顺子
                            $cardType = "straight";
                        } else {
                            $isThreeSameWithTwoDiff = isThreeSameWithTwoDiff($cardArray);
                            if ($isThreeSameWithTwoDiff === "true") { //三条
                                $cardType = "threeSameWithTwoDiff";
                            } else {
                                $isTwoPairWithOneDiff = isTwoPairWithOneDiff($cardArray);
                                // echo "是否是两对:" . $isTwoPairWithOneDiff . "\n";
                                if ($isTwoPairWithOneDiff === "true") { //两对
                                    $cardType = "twoPairWithOneDiff";
                                } else {
                                    $isOnePairWithThreeDiff = isOnePairWithThreeDiff($cardArray);
                                    if ($isOnePairWithThreeDiff === "true") { //对子
                                        $cardType = "onePairWithThreeDiff";
                                    } else {
                                        $cardType = "fiveDiff"; //乌龙
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //如果cardType为空,那么就出错了
        if (empty($cardType)) {
            echo "ERROR: Array==>" . json_encode($cardType) . "<br/>";
        }

        return $cardType;
    }

    /**
     * 判断五张牌是否是特殊牌
     *
     * @param $cardArray
     *
     * @return string
     */
    function isSpecialInFiveCardFix000($cardArray) {
        //["true","tonghua"]
        //["false",""]
        rsort($cardArray);
        $cardType                = "";
        $isFiveCardStraightFlush = isFiveCardStraightFlush($cardArray); //判断是否是同花顺
        if ($isFiveCardStraightFlush == "true") { //同花顺
            $cardType = "straightFlush";
        } else {
            $isTie = isTie($cardArray);
            if ($isTie == "true") { //铁支
                $cardType = "fourSameWithOneDiff";
            } else {
                $isFullThreeSameWithPair = isFullThreeSameWithPair($cardArray);
                if ($isFullThreeSameWithPair == "true") { //葫芦
                    $cardType = "threeSameWithPair";
                } else {
                    $isFullSuit = isFullSuit($cardArray);
                    if ($isFullSuit == "true") { //同花
                        $cardType = "fiveSameSuit";
                    } else {
                        $isStraight = isStraight($cardArray);
                        if ($isStraight == "true") { //顺子
                            $cardType = "straight";
                        } else {
                            $isThreeSameWithTwoDiff = isThreeSameWithTwoDiff($cardArray);
                            if ($isThreeSameWithTwoDiff == "true") { //三条
                                $cardType = "threeSameWithTwoDiff";
                            } else {
                                $isTwoPairWithOneDiff = isTwoPairWithOneDiff($cardArray);
                                if ($isTwoPairWithOneDiff == "true") { //两对
                                    $cardType = "twoPairWithOneDiff";
                                } else {
                                    $isOnePairWithThreeDiff = isOnePairWithThreeDiff($cardArray);
                                    if ($isOnePairWithThreeDiff == "true") { //对子
                                        $cardType = "onePairWithThreeDiff";
                                    } else {
                                        $cardType = "fiveDiff"; //乌龙
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $cardType;
    }

    /**
     * 获取五张牌的牌型详细信息
     */
    function getSpecialTypeInFiveCard($cardArray) {
        rsort($cardArray);
        //牌型
        $cardType = "";
        //针对相同牌型的序列处理
        $index = -1;
        //对子或葫芦在牌中的位置,是处理权重时使用的
        $cardPosition = "";
        //权重
        $priority = -1;

        $isFiveCardStraightFlush = isFiveCardStraightFlushDetail($cardArray); //判断是否是同花顺

        if ($isFiveCardStraightFlush["flag"] == "true") { //同花顺
            $cardType = "straightFlush";
            $index    = $isFiveCardStraightFlush["index"];
            $priority = 9;
        } else {
            $isTie = isTieDetail($cardArray);
            if ($isTie["flag"] == "true") { //铁支
                $cardType = "fourSameWithOneDiff";
                $index    = $isTie["index"];
                $priority = 8;
            } else {
                $isFullThreeSameWithPair = isFullThreeSameWithPairDetail($cardArray);
                if ($isFullThreeSameWithPair["flag"] == "true") { //葫芦
                    $cardType     = "threeSameWithPair";
                    $index        = $isFullThreeSameWithPair["index"];
                    $cardPosition = $isFullThreeSameWithPair["position"];
                    $priority     = 7;
                } else {
                    $isFullSuit = isFullSuitDetail($cardArray);
                    if ($isFullSuit["flag"] == "true") { //同花
                        $cardType = "fiveSameSuit";
                        $index    = $isFullThreeSameWithPair["index"];
                        $priority = 6;
                    } else {
                        $isStraight = isStraightDictionaryWithSpecialTypeDetail($cardArray);
                        if ($isStraight["flag"] == "true") { //顺子
                            $cardType     = "straight";
                            $index        = $isStraight["index"];
                            $cardPosition = $isStraight["position"];
                            $priority     = 5;
                        } else {
                            $isThreeSameWithTwoDiff = isThreeSameWithTwoDiffDetail($cardArray);
                            if ($isThreeSameWithTwoDiff["flag"] == "true") { //三条
                                $cardType     = "threeSameWithTwoDiff";
                                $index        = $isThreeSameWithTwoDiff["index"];
                                $cardPosition = $isThreeSameWithTwoDiff["position"];
                                $priority     = 4;
                            } else {
                                $isTwoPairWithOneDiff = isTwoPairWithOneDiffDetail($cardArray);
                                if ($isTwoPairWithOneDiff["flag"] == "true") { //两对
                                    $cardType     = "twoPairWithOneDiff";
                                    $index        = $isTwoPairWithOneDiff["index"];
                                    $cardPosition = $isTwoPairWithOneDiff["position"];
                                    $priority     = 3;

                                    echo "-----------------测试两对 开始-----------";
                                    echo "cardArray:" . json_encode($cardArray) . "\n";
                                    echo "pairWithOneDiff:" . json_encode($isTwoPairWithOneDiff) . "\n";
                                    echo "-----------------测试两对 结束-----------";
                                } else {
                                    $isOnePairWithThreeDiffDetail = isOnePairWithThreeDiffDetail($cardArray);
                                    if ($isOnePairWithThreeDiffDetail["flag"] == "true") {
                                        $cardType     = "onePairWithThreeDiff";
                                        $index        = $isOnePairWithThreeDiffDetail["index"];
                                        $cardPosition = $isOnePairWithThreeDiffDetail["position"];
                                        $priority     = 2;
                                    } else { //乌龙
                                        $cardType = "fiveDiff";
                                        $priority = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $returnInfo             = [];
        $returnInfo["card"]     = $cardArray;
        $returnInfo["type"]     = $cardType;
        $returnInfo["index"]    = $index;
        $returnInfo["position"] = $cardPosition;
        $returnInfo["priority"] = $priority;

        return $returnInfo;
    }

    /**
     * =============================================================================
     * 牌型判断 开始
     * =============================================================================
     */
    /**
     * 判断五张牌是否是同花顺
     * 同花顺的情况:
     * =============================================================================================================
     * A,K,Q,J,10
     * K,Q,J,10,9
     * Q,J,10,9,8
     * J,10,9,8,7
     * 10,9,8,7,6
     * 9,8,7,6,5
     * 8,7,6,5,4
     * 7,6,5,4,3
     * 6,5,4,3,2
     * =============================================================================================================
     * 算上花色(4种),一共是36种情况,分别是
     * A,K,Q,J,10
     * 黑桃: 52,48,44,40,36
     * 红心: 51,47,43,39,35
     * 梅花: 50,46,42,38,34
     * 方块: 49,45,41,37,33
     * K,Q,J,10,9
     * 黑桃: 48,44,40,36,32
     * 红心: 47,43,39,35,31
     * 梅花: 46,42,38,34,30
     * 方块: 45,41,37,33,29
     * Q,J,10,9,8
     * 黑桃: 44,40,36,32,28
     * 红心: 43,39,35,31,27
     * 梅花: 42,38,34,30,26
     * 方块: 41,37,33,29,25
     * J,10,9,8,7
     * 黑桃: 40,36,32,28,24
     * 红心: 39,35,31,27,23
     * 梅花: 38,34,30,26,22
     * 方块: 37,33,29,25,21
     * 10,9,8,7,6
     * 黑桃: 36,32,28,24,20
     * 红心: 35,31,27,23,19
     * 梅花: 34,30,26,22,18
     * 方块: 33,29,25,21,17
     * 9,8,7,6,5
     * 黑桃: 32,28,24,20,16
     * 红心: 31,27,23,19,15
     * 梅花: 30,26,22,18,14
     * 方块: 29,25,21,17,13
     * 8,7,6,5,4
     * 黑桃: 28,24,20,16,12
     * 红心: 27,23,19,15,11
     * 梅花: 26,22,18,14,10
     * 方块: 25,21,17,13,9
     * 7,6,5,4,3
     * 黑桃: 24,20,16,12,8
     * 红心: 23,19,15,11,7
     * 梅花: 22,18,14,10,6
     * 方块: 21,17,13,9,5
     * 6,5,4,3,2
     * 黑桃: 20,16,12,8,4
     * 红心: 19,15,11,7,3
     * 梅花: 18,14,10,6,2
     * 方块: 17,13,9,5,1
     */
    function isFiveCardStraightFlush($cardArray) {
        $isFiveCardStraightFlush = "false";
        // echo "<br/>";
        // echo "遍历传入的数值:";
        // echo "<br/>";
        // foreach ($cardArray as $item) {
        //     echo "数值:" . $item;
        //     echo "<br/>";
        // }
        // echo "<br/>";

        rsort($cardArray);

        // echo "排序后的数值:";
        // echo "<br/>";
        // foreach ($cardArray as $item) {
        //     echo "数值:" . $item;
        //     echo "<br/>";
        // }
        // echo "<br/>";
        if (count($cardArray) == 5) {
            $cardStraightFlushArray = [
                [52, 48, 44, 40, 36],
                [51, 47, 43, 39, 35],
                [50, 46, 42, 38, 34],
                [49, 45, 41, 37, 33],
                [48, 44, 40, 36, 32],
                [47, 43, 39, 35, 31],
                [46, 42, 38, 34, 30],
                [45, 41, 37, 33, 29],
                [44, 40, 36, 32, 28],
                [43, 39, 35, 31, 27],
                [42, 38, 34, 30, 26],
                [41, 37, 33, 29, 25],
                [40, 36, 32, 28, 24],
                [39, 35, 31, 27, 23],
                [38, 34, 30, 26, 22],
                [37, 33, 29, 25, 21],
                [36, 32, 28, 24, 20],
                [35, 31, 27, 23, 19],
                [34, 30, 26, 22, 18],
                [33, 29, 25, 21, 17],
                [32, 28, 24, 20, 16],
                [31, 27, 23, 19, 15],
                [30, 26, 22, 18, 14],
                [29, 25, 21, 17, 13],
                [28, 24, 20, 16, 12],
                [27, 23, 19, 15, 11],
                [26, 22, 18, 14, 10],
                [25, 21, 17, 13, 9],
                [24, 20, 16, 12, 8],
                [23, 19, 15, 11, 7],
                [22, 18, 14, 10, 6],
                [21, 17, 13, 9, 5],
                [20, 16, 12, 8, 4],
                [19, 15, 11, 7, 3],
                [18, 14, 10, 6, 2],
                [17, 13, 9, 5, 1]
            ];

            foreach ($cardStraightFlushArray as $item) {
                if ($cardArray == $item) {
                    $isFiveCardStraightFlush = "true";
                }
            }

            // if ($isFiveCardStraightFlush == "true") {
            //     echo "<br/>";
            //     echo "相同";
            //     echo "<br/>";
            // } else {
            //     echo "<br/>";
            //     echo "不相同";
            //     echo "<br/>";
            // }

        } else {
            echo "CARD_LENGTH_NOT_EQUAL_FIVE";
        }
        return $isFiveCardStraightFlush;
    }

    function isFiveCardStraightFlushDetail($cardArray) {
        $isFiveCardStraightFlush = "false";
        $index                   = -1;
        // echo "<br/>";
        // echo "遍历传入的数值:";
        // echo "<br/>";
        // foreach ($cardArray as $item) {
        //     echo "数值:" . $item;
        //     echo "<br/>";
        // }
        // echo "<br/>";

        rsort($cardArray);

        // echo "排序后的数值:";
        // echo "<br/>";
        // foreach ($cardArray as $item) {
        //     echo "数值:" . $item;
        //     echo "<br/>";
        // }
        // echo "<br/>";
        if (count($cardArray) == 5) {
            $cardStraightFlushArray = [
                [52, 48, 44, 40, 36],
                [51, 47, 43, 39, 35],
                [50, 46, 42, 38, 34],
                [49, 45, 41, 37, 33],
                //------------------- 特殊牌型 开始 ----------------------
                //A 5 4 3 2 --> A 2 3 4 5
                [52, 16, 12, 8, 4],
                [51, 15, 11, 7, 3],
                [50, 14, 10, 6, 2],
                [49, 13, 9, 5, 1],
                //------------------- 特殊牌型 结束 ----------------------
                [48, 44, 40, 36, 32],
                [47, 43, 39, 35, 31],
                [46, 42, 38, 34, 30],
                [45, 41, 37, 33, 29],
                [44, 40, 36, 32, 28],
                [43, 39, 35, 31, 27],
                [42, 38, 34, 30, 26],
                [41, 37, 33, 29, 25],
                [40, 36, 32, 28, 24],
                [39, 35, 31, 27, 23],
                [38, 34, 30, 26, 22],
                [37, 33, 29, 25, 21],
                [36, 32, 28, 24, 20],
                [35, 31, 27, 23, 19],
                [34, 30, 26, 22, 18],
                [33, 29, 25, 21, 17],
                [32, 28, 24, 20, 16],
                [31, 27, 23, 19, 15],
                [30, 26, 22, 18, 14],
                [29, 25, 21, 17, 13],
                [28, 24, 20, 16, 12],
                [27, 23, 19, 15, 11],
                [26, 22, 18, 14, 10],
                [25, 21, 17, 13, 9],
                [24, 20, 16, 12, 8],
                [23, 19, 15, 11, 7],
                [22, 18, 14, 10, 6],
                [21, 17, 13, 9, 5],
                [20, 16, 12, 8, 4],
                [19, 15, 11, 7, 3],
                [18, 14, 10, 6, 2],
                [17, 13, 9, 5, 1]
            ];

            foreach ($cardStraightFlushArray as $key => $item) {
                if ($cardArray == $item) {
                    $isFiveCardStraightFlush = "true";
                    $index                   = $key;
                }
            }

            // if ($isFiveCardStraightFlush == "true") {
            //     echo "<br/>";
            //     echo "相同";
            //     echo "<br/>";
            // } else {
            //     echo "<br/>";
            //     echo "不相同";
            //     echo "<br/>";
            // }

        } else {
            echo "CARD_LENGTH_NOT_EQUAL_FIVE";
        }

        $returnInfo          = [];
        $returnInfo["flag"]  = $isFiveCardStraightFlush;
        $returnInfo["index"] = $index;
        return $returnInfo;
    }

    /**
     * 是否是铁支的情况
     * 铁支的情况是:
     * 13种全花色+另一张不同,一种全花色搭配12张其他牌[全花色4种],就会有12种情况,13*12*4=624种情况,他们分别是
     * =============================================================================================================
     * 全A+[K,Q,J,10,9,8,7,6,5,4,3,2]
     * [A]+全K+[Q,J,10,9,8,7,6,5,4,3,2]
     * [A,K]+全Q+[J,10,9,8,7,6,5,4,3,2]
     * [A,K,Q]+全J+[10,9,8,7,6,5,4,3,2]
     * [A,K,Q,J]+全10+[9,8,7,6,5,4,3,2]
     * [A,K,Q,J,10]+全9+[8,7,6,5,4,3,2]
     * [A,K,Q,J,10,9]+全8+[7,6,5,4,3,2]
     * [A,K,Q,J,10,9,8]+全7+[6,5,4,3,2]
     * [A,K,Q,J,10,9,8,7]+全6+[5,4,3,2]
     * [A,K,Q,J,10,9,8,7,6]+全5+[4,3,2]
     * [A,K,Q,J,10,9,8,7,6,5]+全4+[3,2]
     * [A,K,Q,J,10,9,8,7,6,5,4]+全3+[2]
     * [A,K,Q,J,10,9,8,7,6,5,4,3]+全2
     * =============================================================================================================
     * 随机种子详情:
     * 全A:[52,51,50,49]
     * 全K:[48,47,46,45]
     * 全Q:[44,43,42,41]
     * 全J:[40,39,38,37]
     * 全10:[36,35,34,33]
     * 全9:[32,31,30,29]
     * 全8:[28,27,26,25]
     * 全7:[24,23,22,21]
     * 全6:[20,19,18,17]
     * 全5:[16,15,14,13]
     * 全4:[12,11,10,9]
     * 全3:[8,7,6,5]
     * 全2:[4,3,2,1]
     * =============================================================================================================
     * 全部情况:
     * //全A
     * 全A+[K,Q,J,10,9,8,7,6,5,4,3,2]:
     * [52,51,50,49]+ range[48,1]
     * //全K
     * [A]+全K+[Q,J,10,9,8,7,6,5,4,3,2]:
     * range[52,49]+[48,47,46,45]+range[44,1]
     * //全Q
     * [A,K]+全Q+[J,10,9,8,7,6,5,4,3,2]
     * range[52,45]+[44,43,42,41]+range[40,1]
     * //全J
     * [A,K,Q]+全J+[10,9,8,7,6,5,4,3,2]
     * range[52,41]+[40,39,38,37]+range[36,1]
     * //全10
     * [A,K,Q,J]+全10+[9,8,7,6,5,4,3,2]
     * range[52,37]+[36,35,34,33]+range[32,1]
     * //全9
     * [A,K,Q,J,10]+全9+[8,7,6,5,4,3,2]
     * range[52,33]+[32,31,30,29]+range[28,1]
     * //全8
     * [A,K,Q,J,10,9]+全8+[7,6,5,4,3,2]
     * range[52,29]+[28,27,26,25]+range[24,1]
     * //全7
     * [A,K,Q,J,10,9,8]+全7+[6,5,4,3,2]
     * range[52,25]+[24,23,22,21]+range[20,1]
     * //全6
     * [A,K,Q,J,10,9,8,7]+全6+[5,4,3,2]
     * range[52,21]+[20,19,18,17]+range[16,1]
     * //全5
     * [A,K,Q,J,10,9,8,7,6]+全5+[4,3,2]
     * range[52,17]+[16,15,14,13]+range[12,1]
     * //全4
     * [A,K,Q,J,10,9,8,7,6,5]+全4+[3,2]
     * range[52,13]+[12,11,10,9]+range[8,1]
     * //全3
     * [A,K,Q,J,10,9,8,7,6,5,4]+全3+[2]
     * range[52,9]+[8,7,6,5]+range[4,1]
     * //全2
     * [A,K,Q,J,10,9,8,7,6,5,4,3]+全2
     * range[52,5]+[4,3,2,1]
     */
    function getAllTie() {
        //全A
        //全A+[K,Q,J,10,9,8,7,6,5,4,3,2]:
        //[52,51,50,49]+ range[48,1]
        $fullArray = [];
        for ($i = 48; $i >= 1; $i--) {
            $cardArray = [52, 51, 50, 49];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["A"][$i] = $cardArray;
        }

        //全K
        //[A]+全K+[Q,J,10,9,8,7,6,5,4,3,2]:
        //range[52,49]+[48,47,46,45]+range[44,1]
        for ($i = 52; $i >= 49; $i--) {
            $cardArray = [48, 47, 46, 45];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["K"][$i] = $cardArray;
        }

        for ($i = 44; $i >= 1; $i--) {
            $cardArray = [48, 47, 46, 45];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["K"][$i] = $cardArray;
        }

        //全Q
        //[A,K]+全Q+[J,10,9,8,7,6,5,4,3,2]
        //range[52,45]+[44,43,42,41]+range[40,1]
        for ($i = 52; $i >= 45; $i--) {
            $cardArray = [44, 43, 42, 41];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["Q"][$i] = $cardArray;
        }

        for ($i = 40; $i >= 1; $i--) {
            $cardArray = [44, 43, 42, 41];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["Q"][$i] = $cardArray;
        }

        //全J
        //[A,K,Q]+全J+[10,9,8,7,6,5,4,3,2]
        //range[52,41]+[40,39,38,37]+range[36,1]
        for ($i = 52; $i >= 41; $i--) {
            $cardArray = [40, 39, 38, 37];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["J"][$i] = $cardArray;
        }

        for ($i = 36; $i >= 1; $i--) {
            $cardArray = [40, 39, 38, 37];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["J"][$i] = $cardArray;
        }

        //全10
        //[A,K,Q,J]+全10+[9,8,7,6,5,4,3,2]
        //range[52,37]+[36,35,34,33]+range[32,1]
        for ($i = 52; $i >= 37; $i--) {
            $cardArray = [36, 35, 34, 33];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["10"][$i] = $cardArray;
        }

        for ($i = 32; $i >= 1; $i--) {
            $cardArray = [36, 35, 34, 33];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["10"][$i] = $cardArray;
        }

        //全9
        //[A,K,Q,J,10]+全9+[8,7,6,5,4,3,2]
        //range[52,33]+[32,31,30,29]+range[28,1]
        for ($i = 52; $i >= 33; $i--) {
            $cardArray = [32, 31, 30, 29];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["9"][$i] = $cardArray;
        }

        for ($i = 28; $i >= 1; $i--) {
            $cardArray = [32, 31, 30, 29];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["9"][$i] = $cardArray;
        }

        //全8
        //[A,K,Q,J,10,9]+全8+[7,6,5,4,3,2]
        //range[52,29]+[28,27,26,25]+range[24,1]
        for ($i = 52; $i >= 29; $i--) {
            $cardArray = [28, 27, 26, 25];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["8"][$i] = $cardArray;
        }

        for ($i = 24; $i >= 1; $i--) {
            $cardArray = [28, 27, 26, 25];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["8"][$i] = $cardArray;
        }

        //全7
        //[A,K,Q,J,10,9,8]+全7+[6,5,4,3,2]
        //range[52,25]+[24,23,22,21]+range[20,1]
        for ($i = 52; $i >= 25; $i--) {
            $cardArray = [24, 23, 22, 21];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["7"][$i] = $cardArray;
        }

        for ($i = 20; $i >= 1; $i--) {
            $cardArray = [24, 23, 22, 21];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["7"][$i] = $cardArray;
        }

        //全6
        //[A,K,Q,J,10,9,8,7]+全6+[5,4,3,2]
        //range[52,21]+[20,19,18,17]+range[16,1]
        for ($i = 52; $i >= 21; $i--) {
            $cardArray = [20, 19, 18, 17];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["6"][$i] = $cardArray;
        }

        for ($i = 16; $i >= 1; $i--) {
            $cardArray = [20, 19, 18, 17];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["6"][$i] = $cardArray;
        }

        //全5
        //[A,K,Q,J,10,9,8,7,6]+全5+[4,3,2]
        //range[52,17]+[16,15,14,13]+range[12,1]
        for ($i = 52; $i >= 17; $i--) {
            $cardArray = [16, 15, 14, 13];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["5"][$i] = $cardArray;
        }

        for ($i = 12; $i >= 1; $i--) {
            $cardArray = [16, 15, 14, 13];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["5"][$i] = $cardArray;
        }

        //全4
        //[A,K,Q,J,10,9,8,7,6,5]+全4+[3,2]
        //range[52,13]+[12,11,10,9]+range[8,1]
        for ($i = 52; $i >= 13; $i--) {
            $cardArray = [12, 11, 10, 9];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["4"][$i] = $cardArray;
        }

        for ($i = 8; $i >= 1; $i--) {
            $cardArray = [12, 11, 10, 9];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["4"][$i] = $cardArray;
        }

        //全3
        //[A,K,Q,J,10,9,8,7,6,5,4]+全3+[2]
        //range[52,9]+[8,7,6,5]+range[4,1]
        for ($i = 52; $i >= 9; $i--) {
            $cardArray = [8, 7, 6, 5];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["3"][$i] = $cardArray;
        }

        for ($i = 4; $i >= 1; $i--) {
            $cardArray = [8, 7, 6, 5];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["3"][$i] = $cardArray;
        }

        //全2
        //[A,K,Q,J,10,9,8,7,6,5,4,3]+全2
        //range[52,5]+[4,3,2,1]
        for ($i = 52; $i >= 5; $i--) {
            $cardArray = [4, 3, 2, 1];
            array_push($cardArray, $i);
            rsort($cardArray);
            $fullArray["2"][$i] = $cardArray;
        }

        /**
         * 遍历所有元素
         */
        foreach ($fullArray as $key => $item) {
            echo "//" . $key . ":";
            echo "<br/>";
            foreach ($item as $key1 => $item1) {
                echo "[" . $item1[0] . "," . $item1[1] . "," . $item1[2] . "," . $item1[3] . "," . $item1[4] . "],";
                echo "<br/>";
            }
        }
    }

    /**
     * 是否是铁支
     */
    function isTie($cardArray) {
        $isTie = "false";
        rsort($cardArray);
        if (count($cardArray) == 5) {
            $cardTieArray = [
                //A:
                [52, 51, 50, 49, 48],
                [52, 51, 50, 49, 47],
                [52, 51, 50, 49, 46],
                [52, 51, 50, 49, 45],
                [52, 51, 50, 49, 44],
                [52, 51, 50, 49, 43],
                [52, 51, 50, 49, 42],
                [52, 51, 50, 49, 41],
                [52, 51, 50, 49, 40],
                [52, 51, 50, 49, 39],
                [52, 51, 50, 49, 38],
                [52, 51, 50, 49, 37],
                [52, 51, 50, 49, 36],
                [52, 51, 50, 49, 35],
                [52, 51, 50, 49, 34],
                [52, 51, 50, 49, 33],
                [52, 51, 50, 49, 32],
                [52, 51, 50, 49, 31],
                [52, 51, 50, 49, 30],
                [52, 51, 50, 49, 29],
                [52, 51, 50, 49, 28],
                [52, 51, 50, 49, 27],
                [52, 51, 50, 49, 26],
                [52, 51, 50, 49, 25],
                [52, 51, 50, 49, 24],
                [52, 51, 50, 49, 23],
                [52, 51, 50, 49, 22],
                [52, 51, 50, 49, 21],
                [52, 51, 50, 49, 20],
                [52, 51, 50, 49, 19],
                [52, 51, 50, 49, 18],
                [52, 51, 50, 49, 17],
                [52, 51, 50, 49, 16],
                [52, 51, 50, 49, 15],
                [52, 51, 50, 49, 14],
                [52, 51, 50, 49, 13],
                [52, 51, 50, 49, 12],
                [52, 51, 50, 49, 11],
                [52, 51, 50, 49, 10],
                [52, 51, 50, 49, 9],
                [52, 51, 50, 49, 8],
                [52, 51, 50, 49, 7],
                [52, 51, 50, 49, 6],
                [52, 51, 50, 49, 5],
                [52, 51, 50, 49, 4],
                [52, 51, 50, 49, 3],
                [52, 51, 50, 49, 2],
                [52, 51, 50, 49, 1],
                //K:
                [52, 48, 47, 46, 45],
                [51, 48, 47, 46, 45],
                [50, 48, 47, 46, 45],
                [49, 48, 47, 46, 45],
                [48, 47, 46, 45, 44],
                [48, 47, 46, 45, 43],
                [48, 47, 46, 45, 42],
                [48, 47, 46, 45, 41],
                [48, 47, 46, 45, 40],
                [48, 47, 46, 45, 39],
                [48, 47, 46, 45, 38],
                [48, 47, 46, 45, 37],
                [48, 47, 46, 45, 36],
                [48, 47, 46, 45, 35],
                [48, 47, 46, 45, 34],
                [48, 47, 46, 45, 33],
                [48, 47, 46, 45, 32],
                [48, 47, 46, 45, 31],
                [48, 47, 46, 45, 30],
                [48, 47, 46, 45, 29],
                [48, 47, 46, 45, 28],
                [48, 47, 46, 45, 27],
                [48, 47, 46, 45, 26],
                [48, 47, 46, 45, 25],
                [48, 47, 46, 45, 24],
                [48, 47, 46, 45, 23],
                [48, 47, 46, 45, 22],
                [48, 47, 46, 45, 21],
                [48, 47, 46, 45, 20],
                [48, 47, 46, 45, 19],
                [48, 47, 46, 45, 18],
                [48, 47, 46, 45, 17],
                [48, 47, 46, 45, 16],
                [48, 47, 46, 45, 15],
                [48, 47, 46, 45, 14],
                [48, 47, 46, 45, 13],
                [48, 47, 46, 45, 12],
                [48, 47, 46, 45, 11],
                [48, 47, 46, 45, 10],
                [48, 47, 46, 45, 9],
                [48, 47, 46, 45, 8],
                [48, 47, 46, 45, 7],
                [48, 47, 46, 45, 6],
                [48, 47, 46, 45, 5],
                [48, 47, 46, 45, 4],
                [48, 47, 46, 45, 3],
                [48, 47, 46, 45, 2],
                [48, 47, 46, 45, 1],
                //Q:
                [52, 44, 43, 42, 41],
                [51, 44, 43, 42, 41],
                [50, 44, 43, 42, 41],
                [49, 44, 43, 42, 41],
                [48, 44, 43, 42, 41],
                [47, 44, 43, 42, 41],
                [46, 44, 43, 42, 41],
                [45, 44, 43, 42, 41],
                [44, 43, 42, 41, 40],
                [44, 43, 42, 41, 39],
                [44, 43, 42, 41, 38],
                [44, 43, 42, 41, 37],
                [44, 43, 42, 41, 36],
                [44, 43, 42, 41, 35],
                [44, 43, 42, 41, 34],
                [44, 43, 42, 41, 33],
                [44, 43, 42, 41, 32],
                [44, 43, 42, 41, 31],
                [44, 43, 42, 41, 30],
                [44, 43, 42, 41, 29],
                [44, 43, 42, 41, 28],
                [44, 43, 42, 41, 27],
                [44, 43, 42, 41, 26],
                [44, 43, 42, 41, 25],
                [44, 43, 42, 41, 24],
                [44, 43, 42, 41, 23],
                [44, 43, 42, 41, 22],
                [44, 43, 42, 41, 21],
                [44, 43, 42, 41, 20],
                [44, 43, 42, 41, 19],
                [44, 43, 42, 41, 18],
                [44, 43, 42, 41, 17],
                [44, 43, 42, 41, 16],
                [44, 43, 42, 41, 15],
                [44, 43, 42, 41, 14],
                [44, 43, 42, 41, 13],
                [44, 43, 42, 41, 12],
                [44, 43, 42, 41, 11],
                [44, 43, 42, 41, 10],
                [44, 43, 42, 41, 9],
                [44, 43, 42, 41, 8],
                [44, 43, 42, 41, 7],
                [44, 43, 42, 41, 6],
                [44, 43, 42, 41, 5],
                [44, 43, 42, 41, 4],
                [44, 43, 42, 41, 3],
                [44, 43, 42, 41, 2],
                [44, 43, 42, 41, 1],
                //J:
                [52, 40, 39, 38, 37],
                [51, 40, 39, 38, 37],
                [50, 40, 39, 38, 37],
                [49, 40, 39, 38, 37],
                [48, 40, 39, 38, 37],
                [47, 40, 39, 38, 37],
                [46, 40, 39, 38, 37],
                [45, 40, 39, 38, 37],
                [44, 40, 39, 38, 37],
                [43, 40, 39, 38, 37],
                [42, 40, 39, 38, 37],
                [41, 40, 39, 38, 37],
                [40, 39, 38, 37, 36],
                [40, 39, 38, 37, 35],
                [40, 39, 38, 37, 34],
                [40, 39, 38, 37, 33],
                [40, 39, 38, 37, 32],
                [40, 39, 38, 37, 31],
                [40, 39, 38, 37, 30],
                [40, 39, 38, 37, 29],
                [40, 39, 38, 37, 28],
                [40, 39, 38, 37, 27],
                [40, 39, 38, 37, 26],
                [40, 39, 38, 37, 25],
                [40, 39, 38, 37, 24],
                [40, 39, 38, 37, 23],
                [40, 39, 38, 37, 22],
                [40, 39, 38, 37, 21],
                [40, 39, 38, 37, 20],
                [40, 39, 38, 37, 19],
                [40, 39, 38, 37, 18],
                [40, 39, 38, 37, 17],
                [40, 39, 38, 37, 16],
                [40, 39, 38, 37, 15],
                [40, 39, 38, 37, 14],
                [40, 39, 38, 37, 13],
                [40, 39, 38, 37, 12],
                [40, 39, 38, 37, 11],
                [40, 39, 38, 37, 10],
                [40, 39, 38, 37, 9],
                [40, 39, 38, 37, 8],
                [40, 39, 38, 37, 7],
                [40, 39, 38, 37, 6],
                [40, 39, 38, 37, 5],
                [40, 39, 38, 37, 4],
                [40, 39, 38, 37, 3],
                [40, 39, 38, 37, 2],
                [40, 39, 38, 37, 1],
                //10:
                [52, 36, 35, 34, 33],
                [51, 36, 35, 34, 33],
                [50, 36, 35, 34, 33],
                [49, 36, 35, 34, 33],
                [48, 36, 35, 34, 33],
                [47, 36, 35, 34, 33],
                [46, 36, 35, 34, 33],
                [45, 36, 35, 34, 33],
                [44, 36, 35, 34, 33],
                [43, 36, 35, 34, 33],
                [42, 36, 35, 34, 33],
                [41, 36, 35, 34, 33],
                [40, 36, 35, 34, 33],
                [39, 36, 35, 34, 33],
                [38, 36, 35, 34, 33],
                [37, 36, 35, 34, 33],
                [36, 35, 34, 33, 32],
                [36, 35, 34, 33, 31],
                [36, 35, 34, 33, 30],
                [36, 35, 34, 33, 29],
                [36, 35, 34, 33, 28],
                [36, 35, 34, 33, 27],
                [36, 35, 34, 33, 26],
                [36, 35, 34, 33, 25],
                [36, 35, 34, 33, 24],
                [36, 35, 34, 33, 23],
                [36, 35, 34, 33, 22],
                [36, 35, 34, 33, 21],
                [36, 35, 34, 33, 20],
                [36, 35, 34, 33, 19],
                [36, 35, 34, 33, 18],
                [36, 35, 34, 33, 17],
                [36, 35, 34, 33, 16],
                [36, 35, 34, 33, 15],
                [36, 35, 34, 33, 14],
                [36, 35, 34, 33, 13],
                [36, 35, 34, 33, 12],
                [36, 35, 34, 33, 11],
                [36, 35, 34, 33, 10],
                [36, 35, 34, 33, 9],
                [36, 35, 34, 33, 8],
                [36, 35, 34, 33, 7],
                [36, 35, 34, 33, 6],
                [36, 35, 34, 33, 5],
                [36, 35, 34, 33, 4],
                [36, 35, 34, 33, 3],
                [36, 35, 34, 33, 2],
                [36, 35, 34, 33, 1],
                //9:
                [52, 32, 31, 30, 29],
                [51, 32, 31, 30, 29],
                [50, 32, 31, 30, 29],
                [49, 32, 31, 30, 29],
                [48, 32, 31, 30, 29],
                [47, 32, 31, 30, 29],
                [46, 32, 31, 30, 29],
                [45, 32, 31, 30, 29],
                [44, 32, 31, 30, 29],
                [43, 32, 31, 30, 29],
                [42, 32, 31, 30, 29],
                [41, 32, 31, 30, 29],
                [40, 32, 31, 30, 29],
                [39, 32, 31, 30, 29],
                [38, 32, 31, 30, 29],
                [37, 32, 31, 30, 29],
                [36, 32, 31, 30, 29],
                [35, 32, 31, 30, 29],
                [34, 32, 31, 30, 29],
                [33, 32, 31, 30, 29],
                [32, 31, 30, 29, 28],
                [32, 31, 30, 29, 27],
                [32, 31, 30, 29, 26],
                [32, 31, 30, 29, 25],
                [32, 31, 30, 29, 24],
                [32, 31, 30, 29, 23],
                [32, 31, 30, 29, 22],
                [32, 31, 30, 29, 21],
                [32, 31, 30, 29, 20],
                [32, 31, 30, 29, 19],
                [32, 31, 30, 29, 18],
                [32, 31, 30, 29, 17],
                [32, 31, 30, 29, 16],
                [32, 31, 30, 29, 15],
                [32, 31, 30, 29, 14],
                [32, 31, 30, 29, 13],
                [32, 31, 30, 29, 12],
                [32, 31, 30, 29, 11],
                [32, 31, 30, 29, 10],
                [32, 31, 30, 29, 9],
                [32, 31, 30, 29, 8],
                [32, 31, 30, 29, 7],
                [32, 31, 30, 29, 6],
                [32, 31, 30, 29, 5],
                [32, 31, 30, 29, 4],
                [32, 31, 30, 29, 3],
                [32, 31, 30, 29, 2],
                [32, 31, 30, 29, 1],
                //8:
                [52, 28, 27, 26, 25],
                [51, 28, 27, 26, 25],
                [50, 28, 27, 26, 25],
                [49, 28, 27, 26, 25],
                [48, 28, 27, 26, 25],
                [47, 28, 27, 26, 25],
                [46, 28, 27, 26, 25],
                [45, 28, 27, 26, 25],
                [44, 28, 27, 26, 25],
                [43, 28, 27, 26, 25],
                [42, 28, 27, 26, 25],
                [41, 28, 27, 26, 25],
                [40, 28, 27, 26, 25],
                [39, 28, 27, 26, 25],
                [38, 28, 27, 26, 25],
                [37, 28, 27, 26, 25],
                [36, 28, 27, 26, 25],
                [35, 28, 27, 26, 25],
                [34, 28, 27, 26, 25],
                [33, 28, 27, 26, 25],
                [32, 28, 27, 26, 25],
                [31, 28, 27, 26, 25],
                [30, 28, 27, 26, 25],
                [29, 28, 27, 26, 25],
                [28, 27, 26, 25, 24],
                [28, 27, 26, 25, 23],
                [28, 27, 26, 25, 22],
                [28, 27, 26, 25, 21],
                [28, 27, 26, 25, 20],
                [28, 27, 26, 25, 19],
                [28, 27, 26, 25, 18],
                [28, 27, 26, 25, 17],
                [28, 27, 26, 25, 16],
                [28, 27, 26, 25, 15],
                [28, 27, 26, 25, 14],
                [28, 27, 26, 25, 13],
                [28, 27, 26, 25, 12],
                [28, 27, 26, 25, 11],
                [28, 27, 26, 25, 10],
                [28, 27, 26, 25, 9],
                [28, 27, 26, 25, 8],
                [28, 27, 26, 25, 7],
                [28, 27, 26, 25, 6],
                [28, 27, 26, 25, 5],
                [28, 27, 26, 25, 4],
                [28, 27, 26, 25, 3],
                [28, 27, 26, 25, 2],
                [28, 27, 26, 25, 1],
                //7:
                [52, 24, 23, 22, 21],
                [51, 24, 23, 22, 21],
                [50, 24, 23, 22, 21],
                [49, 24, 23, 22, 21],
                [48, 24, 23, 22, 21],
                [47, 24, 23, 22, 21],
                [46, 24, 23, 22, 21],
                [45, 24, 23, 22, 21],
                [44, 24, 23, 22, 21],
                [43, 24, 23, 22, 21],
                [42, 24, 23, 22, 21],
                [41, 24, 23, 22, 21],
                [40, 24, 23, 22, 21],
                [39, 24, 23, 22, 21],
                [38, 24, 23, 22, 21],
                [37, 24, 23, 22, 21],
                [36, 24, 23, 22, 21],
                [35, 24, 23, 22, 21],
                [34, 24, 23, 22, 21],
                [33, 24, 23, 22, 21],
                [32, 24, 23, 22, 21],
                [31, 24, 23, 22, 21],
                [30, 24, 23, 22, 21],
                [29, 24, 23, 22, 21],
                [28, 24, 23, 22, 21],
                [27, 24, 23, 22, 21],
                [26, 24, 23, 22, 21],
                [25, 24, 23, 22, 21],
                [24, 23, 22, 21, 20],
                [24, 23, 22, 21, 19],
                [24, 23, 22, 21, 18],
                [24, 23, 22, 21, 17],
                [24, 23, 22, 21, 16],
                [24, 23, 22, 21, 15],
                [24, 23, 22, 21, 14],
                [24, 23, 22, 21, 13],
                [24, 23, 22, 21, 12],
                [24, 23, 22, 21, 11],
                [24, 23, 22, 21, 10],
                [24, 23, 22, 21, 9],
                [24, 23, 22, 21, 8],
                [24, 23, 22, 21, 7],
                [24, 23, 22, 21, 6],
                [24, 23, 22, 21, 5],
                [24, 23, 22, 21, 4],
                [24, 23, 22, 21, 3],
                [24, 23, 22, 21, 2],
                [24, 23, 22, 21, 1],
                //6:
                [52, 20, 19, 18, 17],
                [51, 20, 19, 18, 17],
                [50, 20, 19, 18, 17],
                [49, 20, 19, 18, 17],
                [48, 20, 19, 18, 17],
                [47, 20, 19, 18, 17],
                [46, 20, 19, 18, 17],
                [45, 20, 19, 18, 17],
                [44, 20, 19, 18, 17],
                [43, 20, 19, 18, 17],
                [42, 20, 19, 18, 17],
                [41, 20, 19, 18, 17],
                [40, 20, 19, 18, 17],
                [39, 20, 19, 18, 17],
                [38, 20, 19, 18, 17],
                [37, 20, 19, 18, 17],
                [36, 20, 19, 18, 17],
                [35, 20, 19, 18, 17],
                [34, 20, 19, 18, 17],
                [33, 20, 19, 18, 17],
                [32, 20, 19, 18, 17],
                [31, 20, 19, 18, 17],
                [30, 20, 19, 18, 17],
                [29, 20, 19, 18, 17],
                [28, 20, 19, 18, 17],
                [27, 20, 19, 18, 17],
                [26, 20, 19, 18, 17],
                [25, 20, 19, 18, 17],
                [24, 20, 19, 18, 17],
                [23, 20, 19, 18, 17],
                [22, 20, 19, 18, 17],
                [21, 20, 19, 18, 17],
                [20, 19, 18, 17, 16],
                [20, 19, 18, 17, 15],
                [20, 19, 18, 17, 14],
                [20, 19, 18, 17, 13],
                [20, 19, 18, 17, 12],
                [20, 19, 18, 17, 11],
                [20, 19, 18, 17, 10],
                [20, 19, 18, 17, 9],
                [20, 19, 18, 17, 8],
                [20, 19, 18, 17, 7],
                [20, 19, 18, 17, 6],
                [20, 19, 18, 17, 5],
                [20, 19, 18, 17, 4],
                [20, 19, 18, 17, 3],
                [20, 19, 18, 17, 2],
                [20, 19, 18, 17, 1],
                //5:
                [52, 16, 15, 14, 13],
                [51, 16, 15, 14, 13],
                [50, 16, 15, 14, 13],
                [49, 16, 15, 14, 13],
                [48, 16, 15, 14, 13],
                [47, 16, 15, 14, 13],
                [46, 16, 15, 14, 13],
                [45, 16, 15, 14, 13],
                [44, 16, 15, 14, 13],
                [43, 16, 15, 14, 13],
                [42, 16, 15, 14, 13],
                [41, 16, 15, 14, 13],
                [40, 16, 15, 14, 13],
                [39, 16, 15, 14, 13],
                [38, 16, 15, 14, 13],
                [37, 16, 15, 14, 13],
                [36, 16, 15, 14, 13],
                [35, 16, 15, 14, 13],
                [34, 16, 15, 14, 13],
                [33, 16, 15, 14, 13],
                [32, 16, 15, 14, 13],
                [31, 16, 15, 14, 13],
                [30, 16, 15, 14, 13],
                [29, 16, 15, 14, 13],
                [28, 16, 15, 14, 13],
                [27, 16, 15, 14, 13],
                [26, 16, 15, 14, 13],
                [25, 16, 15, 14, 13],
                [24, 16, 15, 14, 13],
                [23, 16, 15, 14, 13],
                [22, 16, 15, 14, 13],
                [21, 16, 15, 14, 13],
                [20, 16, 15, 14, 13],
                [19, 16, 15, 14, 13],
                [18, 16, 15, 14, 13],
                [17, 16, 15, 14, 13],
                [16, 15, 14, 13, 12],
                [16, 15, 14, 13, 11],
                [16, 15, 14, 13, 10],
                [16, 15, 14, 13, 9],
                [16, 15, 14, 13, 8],
                [16, 15, 14, 13, 7],
                [16, 15, 14, 13, 6],
                [16, 15, 14, 13, 5],
                [16, 15, 14, 13, 4],
                [16, 15, 14, 13, 3],
                [16, 15, 14, 13, 2],
                [16, 15, 14, 13, 1],
                //4:
                [52, 12, 11, 10, 9],
                [51, 12, 11, 10, 9],
                [50, 12, 11, 10, 9],
                [49, 12, 11, 10, 9],
                [48, 12, 11, 10, 9],
                [47, 12, 11, 10, 9],
                [46, 12, 11, 10, 9],
                [45, 12, 11, 10, 9],
                [44, 12, 11, 10, 9],
                [43, 12, 11, 10, 9],
                [42, 12, 11, 10, 9],
                [41, 12, 11, 10, 9],
                [40, 12, 11, 10, 9],
                [39, 12, 11, 10, 9],
                [38, 12, 11, 10, 9],
                [37, 12, 11, 10, 9],
                [36, 12, 11, 10, 9],
                [35, 12, 11, 10, 9],
                [34, 12, 11, 10, 9],
                [33, 12, 11, 10, 9],
                [32, 12, 11, 10, 9],
                [31, 12, 11, 10, 9],
                [30, 12, 11, 10, 9],
                [29, 12, 11, 10, 9],
                [28, 12, 11, 10, 9],
                [27, 12, 11, 10, 9],
                [26, 12, 11, 10, 9],
                [25, 12, 11, 10, 9],
                [24, 12, 11, 10, 9],
                [23, 12, 11, 10, 9],
                [22, 12, 11, 10, 9],
                [21, 12, 11, 10, 9],
                [20, 12, 11, 10, 9],
                [19, 12, 11, 10, 9],
                [18, 12, 11, 10, 9],
                [17, 12, 11, 10, 9],
                [16, 12, 11, 10, 9],
                [15, 12, 11, 10, 9],
                [14, 12, 11, 10, 9],
                [13, 12, 11, 10, 9],
                [12, 11, 10, 9, 8],
                [12, 11, 10, 9, 7],
                [12, 11, 10, 9, 6],
                [12, 11, 10, 9, 5],
                [12, 11, 10, 9, 4],
                [12, 11, 10, 9, 3],
                [12, 11, 10, 9, 2],
                [12, 11, 10, 9, 1],
                //3:
                [52, 8, 7, 6, 5],
                [51, 8, 7, 6, 5],
                [50, 8, 7, 6, 5],
                [49, 8, 7, 6, 5],
                [48, 8, 7, 6, 5],
                [47, 8, 7, 6, 5],
                [46, 8, 7, 6, 5],
                [45, 8, 7, 6, 5],
                [44, 8, 7, 6, 5],
                [43, 8, 7, 6, 5],
                [42, 8, 7, 6, 5],
                [41, 8, 7, 6, 5],
                [40, 8, 7, 6, 5],
                [39, 8, 7, 6, 5],
                [38, 8, 7, 6, 5],
                [37, 8, 7, 6, 5],
                [36, 8, 7, 6, 5],
                [35, 8, 7, 6, 5],
                [34, 8, 7, 6, 5],
                [33, 8, 7, 6, 5],
                [32, 8, 7, 6, 5],
                [31, 8, 7, 6, 5],
                [30, 8, 7, 6, 5],
                [29, 8, 7, 6, 5],
                [28, 8, 7, 6, 5],
                [27, 8, 7, 6, 5],
                [26, 8, 7, 6, 5],
                [25, 8, 7, 6, 5],
                [24, 8, 7, 6, 5],
                [23, 8, 7, 6, 5],
                [22, 8, 7, 6, 5],
                [21, 8, 7, 6, 5],
                [20, 8, 7, 6, 5],
                [19, 8, 7, 6, 5],
                [18, 8, 7, 6, 5],
                [17, 8, 7, 6, 5],
                [16, 8, 7, 6, 5],
                [15, 8, 7, 6, 5],
                [14, 8, 7, 6, 5],
                [13, 8, 7, 6, 5],
                [12, 8, 7, 6, 5],
                [11, 8, 7, 6, 5],
                [10, 8, 7, 6, 5],
                [9, 8, 7, 6, 5],
                [8, 7, 6, 5, 4],
                [8, 7, 6, 5, 3],
                [8, 7, 6, 5, 2],
                [8, 7, 6, 5, 1],
                //2:
                [52, 4, 3, 2, 1],
                [51, 4, 3, 2, 1],
                [50, 4, 3, 2, 1],
                [49, 4, 3, 2, 1],
                [48, 4, 3, 2, 1],
                [47, 4, 3, 2, 1],
                [46, 4, 3, 2, 1],
                [45, 4, 3, 2, 1],
                [44, 4, 3, 2, 1],
                [43, 4, 3, 2, 1],
                [42, 4, 3, 2, 1],
                [41, 4, 3, 2, 1],
                [40, 4, 3, 2, 1],
                [39, 4, 3, 2, 1],
                [38, 4, 3, 2, 1],
                [37, 4, 3, 2, 1],
                [36, 4, 3, 2, 1],
                [35, 4, 3, 2, 1],
                [34, 4, 3, 2, 1],
                [33, 4, 3, 2, 1],
                [32, 4, 3, 2, 1],
                [31, 4, 3, 2, 1],
                [30, 4, 3, 2, 1],
                [29, 4, 3, 2, 1],
                [28, 4, 3, 2, 1],
                [27, 4, 3, 2, 1],
                [26, 4, 3, 2, 1],
                [25, 4, 3, 2, 1],
                [24, 4, 3, 2, 1],
                [23, 4, 3, 2, 1],
                [22, 4, 3, 2, 1],
                [21, 4, 3, 2, 1],
                [20, 4, 3, 2, 1],
                [19, 4, 3, 2, 1],
                [18, 4, 3, 2, 1],
                [17, 4, 3, 2, 1],
                [16, 4, 3, 2, 1],
                [15, 4, 3, 2, 1],
                [14, 4, 3, 2, 1],
                [13, 4, 3, 2, 1],
                [12, 4, 3, 2, 1],
                [11, 4, 3, 2, 1],
                [10, 4, 3, 2, 1],
                [9, 4, 3, 2, 1],
                [8, 4, 3, 2, 1],
                [7, 4, 3, 2, 1],
                [6, 4, 3, 2, 1],
                [5, 4, 3, 2, 1]
            ];

            foreach ($cardTieArray as $item) {
                if ($cardArray == $item) {
                    $isTie = "true";
                }
            }

            // if ($isTie == "true") {
            //     echo "<br/>";
            //     echo "相同";
            //     echo "<br/>";
            // } else {
            //     echo "<br/>";
            //     echo "不相同";
            //     echo "<br/>";
            // }
        } else {
            echo "CARD_LENGTH_NOT_EQUAL_FIVE";
        }
        return $isTie;
    }

    function isTieDetail($cardArray) {
        $isTie = "false";
        $index = -1;
        rsort($cardArray);
        if (count($cardArray) == 5) {
            $cardTieArray = [
                //A:
                [52, 51, 50, 49, 48],
                [52, 51, 50, 49, 47],
                [52, 51, 50, 49, 46],
                [52, 51, 50, 49, 45],
                [52, 51, 50, 49, 44],
                [52, 51, 50, 49, 43],
                [52, 51, 50, 49, 42],
                [52, 51, 50, 49, 41],
                [52, 51, 50, 49, 40],
                [52, 51, 50, 49, 39],
                [52, 51, 50, 49, 38],
                [52, 51, 50, 49, 37],
                [52, 51, 50, 49, 36],
                [52, 51, 50, 49, 35],
                [52, 51, 50, 49, 34],
                [52, 51, 50, 49, 33],
                [52, 51, 50, 49, 32],
                [52, 51, 50, 49, 31],
                [52, 51, 50, 49, 30],
                [52, 51, 50, 49, 29],
                [52, 51, 50, 49, 28],
                [52, 51, 50, 49, 27],
                [52, 51, 50, 49, 26],
                [52, 51, 50, 49, 25],
                [52, 51, 50, 49, 24],
                [52, 51, 50, 49, 23],
                [52, 51, 50, 49, 22],
                [52, 51, 50, 49, 21],
                [52, 51, 50, 49, 20],
                [52, 51, 50, 49, 19],
                [52, 51, 50, 49, 18],
                [52, 51, 50, 49, 17],
                [52, 51, 50, 49, 16],
                [52, 51, 50, 49, 15],
                [52, 51, 50, 49, 14],
                [52, 51, 50, 49, 13],
                [52, 51, 50, 49, 12],
                [52, 51, 50, 49, 11],
                [52, 51, 50, 49, 10],
                [52, 51, 50, 49, 9],
                [52, 51, 50, 49, 8],
                [52, 51, 50, 49, 7],
                [52, 51, 50, 49, 6],
                [52, 51, 50, 49, 5],
                [52, 51, 50, 49, 4],
                [52, 51, 50, 49, 3],
                [52, 51, 50, 49, 2],
                [52, 51, 50, 49, 1],
                //K:
                [52, 48, 47, 46, 45],
                [51, 48, 47, 46, 45],
                [50, 48, 47, 46, 45],
                [49, 48, 47, 46, 45],
                [48, 47, 46, 45, 44],
                [48, 47, 46, 45, 43],
                [48, 47, 46, 45, 42],
                [48, 47, 46, 45, 41],
                [48, 47, 46, 45, 40],
                [48, 47, 46, 45, 39],
                [48, 47, 46, 45, 38],
                [48, 47, 46, 45, 37],
                [48, 47, 46, 45, 36],
                [48, 47, 46, 45, 35],
                [48, 47, 46, 45, 34],
                [48, 47, 46, 45, 33],
                [48, 47, 46, 45, 32],
                [48, 47, 46, 45, 31],
                [48, 47, 46, 45, 30],
                [48, 47, 46, 45, 29],
                [48, 47, 46, 45, 28],
                [48, 47, 46, 45, 27],
                [48, 47, 46, 45, 26],
                [48, 47, 46, 45, 25],
                [48, 47, 46, 45, 24],
                [48, 47, 46, 45, 23],
                [48, 47, 46, 45, 22],
                [48, 47, 46, 45, 21],
                [48, 47, 46, 45, 20],
                [48, 47, 46, 45, 19],
                [48, 47, 46, 45, 18],
                [48, 47, 46, 45, 17],
                [48, 47, 46, 45, 16],
                [48, 47, 46, 45, 15],
                [48, 47, 46, 45, 14],
                [48, 47, 46, 45, 13],
                [48, 47, 46, 45, 12],
                [48, 47, 46, 45, 11],
                [48, 47, 46, 45, 10],
                [48, 47, 46, 45, 9],
                [48, 47, 46, 45, 8],
                [48, 47, 46, 45, 7],
                [48, 47, 46, 45, 6],
                [48, 47, 46, 45, 5],
                [48, 47, 46, 45, 4],
                [48, 47, 46, 45, 3],
                [48, 47, 46, 45, 2],
                [48, 47, 46, 45, 1],
                //Q:
                [52, 44, 43, 42, 41],
                [51, 44, 43, 42, 41],
                [50, 44, 43, 42, 41],
                [49, 44, 43, 42, 41],
                [48, 44, 43, 42, 41],
                [47, 44, 43, 42, 41],
                [46, 44, 43, 42, 41],
                [45, 44, 43, 42, 41],
                [44, 43, 42, 41, 40],
                [44, 43, 42, 41, 39],
                [44, 43, 42, 41, 38],
                [44, 43, 42, 41, 37],
                [44, 43, 42, 41, 36],
                [44, 43, 42, 41, 35],
                [44, 43, 42, 41, 34],
                [44, 43, 42, 41, 33],
                [44, 43, 42, 41, 32],
                [44, 43, 42, 41, 31],
                [44, 43, 42, 41, 30],
                [44, 43, 42, 41, 29],
                [44, 43, 42, 41, 28],
                [44, 43, 42, 41, 27],
                [44, 43, 42, 41, 26],
                [44, 43, 42, 41, 25],
                [44, 43, 42, 41, 24],
                [44, 43, 42, 41, 23],
                [44, 43, 42, 41, 22],
                [44, 43, 42, 41, 21],
                [44, 43, 42, 41, 20],
                [44, 43, 42, 41, 19],
                [44, 43, 42, 41, 18],
                [44, 43, 42, 41, 17],
                [44, 43, 42, 41, 16],
                [44, 43, 42, 41, 15],
                [44, 43, 42, 41, 14],
                [44, 43, 42, 41, 13],
                [44, 43, 42, 41, 12],
                [44, 43, 42, 41, 11],
                [44, 43, 42, 41, 10],
                [44, 43, 42, 41, 9],
                [44, 43, 42, 41, 8],
                [44, 43, 42, 41, 7],
                [44, 43, 42, 41, 6],
                [44, 43, 42, 41, 5],
                [44, 43, 42, 41, 4],
                [44, 43, 42, 41, 3],
                [44, 43, 42, 41, 2],
                [44, 43, 42, 41, 1],
                //J:
                [52, 40, 39, 38, 37],
                [51, 40, 39, 38, 37],
                [50, 40, 39, 38, 37],
                [49, 40, 39, 38, 37],
                [48, 40, 39, 38, 37],
                [47, 40, 39, 38, 37],
                [46, 40, 39, 38, 37],
                [45, 40, 39, 38, 37],
                [44, 40, 39, 38, 37],
                [43, 40, 39, 38, 37],
                [42, 40, 39, 38, 37],
                [41, 40, 39, 38, 37],
                [40, 39, 38, 37, 36],
                [40, 39, 38, 37, 35],
                [40, 39, 38, 37, 34],
                [40, 39, 38, 37, 33],
                [40, 39, 38, 37, 32],
                [40, 39, 38, 37, 31],
                [40, 39, 38, 37, 30],
                [40, 39, 38, 37, 29],
                [40, 39, 38, 37, 28],
                [40, 39, 38, 37, 27],
                [40, 39, 38, 37, 26],
                [40, 39, 38, 37, 25],
                [40, 39, 38, 37, 24],
                [40, 39, 38, 37, 23],
                [40, 39, 38, 37, 22],
                [40, 39, 38, 37, 21],
                [40, 39, 38, 37, 20],
                [40, 39, 38, 37, 19],
                [40, 39, 38, 37, 18],
                [40, 39, 38, 37, 17],
                [40, 39, 38, 37, 16],
                [40, 39, 38, 37, 15],
                [40, 39, 38, 37, 14],
                [40, 39, 38, 37, 13],
                [40, 39, 38, 37, 12],
                [40, 39, 38, 37, 11],
                [40, 39, 38, 37, 10],
                [40, 39, 38, 37, 9],
                [40, 39, 38, 37, 8],
                [40, 39, 38, 37, 7],
                [40, 39, 38, 37, 6],
                [40, 39, 38, 37, 5],
                [40, 39, 38, 37, 4],
                [40, 39, 38, 37, 3],
                [40, 39, 38, 37, 2],
                [40, 39, 38, 37, 1],
                //10:
                [52, 36, 35, 34, 33],
                [51, 36, 35, 34, 33],
                [50, 36, 35, 34, 33],
                [49, 36, 35, 34, 33],
                [48, 36, 35, 34, 33],
                [47, 36, 35, 34, 33],
                [46, 36, 35, 34, 33],
                [45, 36, 35, 34, 33],
                [44, 36, 35, 34, 33],
                [43, 36, 35, 34, 33],
                [42, 36, 35, 34, 33],
                [41, 36, 35, 34, 33],
                [40, 36, 35, 34, 33],
                [39, 36, 35, 34, 33],
                [38, 36, 35, 34, 33],
                [37, 36, 35, 34, 33],
                [36, 35, 34, 33, 32],
                [36, 35, 34, 33, 31],
                [36, 35, 34, 33, 30],
                [36, 35, 34, 33, 29],
                [36, 35, 34, 33, 28],
                [36, 35, 34, 33, 27],
                [36, 35, 34, 33, 26],
                [36, 35, 34, 33, 25],
                [36, 35, 34, 33, 24],
                [36, 35, 34, 33, 23],
                [36, 35, 34, 33, 22],
                [36, 35, 34, 33, 21],
                [36, 35, 34, 33, 20],
                [36, 35, 34, 33, 19],
                [36, 35, 34, 33, 18],
                [36, 35, 34, 33, 17],
                [36, 35, 34, 33, 16],
                [36, 35, 34, 33, 15],
                [36, 35, 34, 33, 14],
                [36, 35, 34, 33, 13],
                [36, 35, 34, 33, 12],
                [36, 35, 34, 33, 11],
                [36, 35, 34, 33, 10],
                [36, 35, 34, 33, 9],
                [36, 35, 34, 33, 8],
                [36, 35, 34, 33, 7],
                [36, 35, 34, 33, 6],
                [36, 35, 34, 33, 5],
                [36, 35, 34, 33, 4],
                [36, 35, 34, 33, 3],
                [36, 35, 34, 33, 2],
                [36, 35, 34, 33, 1],
                //9:
                [52, 32, 31, 30, 29],
                [51, 32, 31, 30, 29],
                [50, 32, 31, 30, 29],
                [49, 32, 31, 30, 29],
                [48, 32, 31, 30, 29],
                [47, 32, 31, 30, 29],
                [46, 32, 31, 30, 29],
                [45, 32, 31, 30, 29],
                [44, 32, 31, 30, 29],
                [43, 32, 31, 30, 29],
                [42, 32, 31, 30, 29],
                [41, 32, 31, 30, 29],
                [40, 32, 31, 30, 29],
                [39, 32, 31, 30, 29],
                [38, 32, 31, 30, 29],
                [37, 32, 31, 30, 29],
                [36, 32, 31, 30, 29],
                [35, 32, 31, 30, 29],
                [34, 32, 31, 30, 29],
                [33, 32, 31, 30, 29],
                [32, 31, 30, 29, 28],
                [32, 31, 30, 29, 27],
                [32, 31, 30, 29, 26],
                [32, 31, 30, 29, 25],
                [32, 31, 30, 29, 24],
                [32, 31, 30, 29, 23],
                [32, 31, 30, 29, 22],
                [32, 31, 30, 29, 21],
                [32, 31, 30, 29, 20],
                [32, 31, 30, 29, 19],
                [32, 31, 30, 29, 18],
                [32, 31, 30, 29, 17],
                [32, 31, 30, 29, 16],
                [32, 31, 30, 29, 15],
                [32, 31, 30, 29, 14],
                [32, 31, 30, 29, 13],
                [32, 31, 30, 29, 12],
                [32, 31, 30, 29, 11],
                [32, 31, 30, 29, 10],
                [32, 31, 30, 29, 9],
                [32, 31, 30, 29, 8],
                [32, 31, 30, 29, 7],
                [32, 31, 30, 29, 6],
                [32, 31, 30, 29, 5],
                [32, 31, 30, 29, 4],
                [32, 31, 30, 29, 3],
                [32, 31, 30, 29, 2],
                [32, 31, 30, 29, 1],
                //8:
                [52, 28, 27, 26, 25],
                [51, 28, 27, 26, 25],
                [50, 28, 27, 26, 25],
                [49, 28, 27, 26, 25],
                [48, 28, 27, 26, 25],
                [47, 28, 27, 26, 25],
                [46, 28, 27, 26, 25],
                [45, 28, 27, 26, 25],
                [44, 28, 27, 26, 25],
                [43, 28, 27, 26, 25],
                [42, 28, 27, 26, 25],
                [41, 28, 27, 26, 25],
                [40, 28, 27, 26, 25],
                [39, 28, 27, 26, 25],
                [38, 28, 27, 26, 25],
                [37, 28, 27, 26, 25],
                [36, 28, 27, 26, 25],
                [35, 28, 27, 26, 25],
                [34, 28, 27, 26, 25],
                [33, 28, 27, 26, 25],
                [32, 28, 27, 26, 25],
                [31, 28, 27, 26, 25],
                [30, 28, 27, 26, 25],
                [29, 28, 27, 26, 25],
                [28, 27, 26, 25, 24],
                [28, 27, 26, 25, 23],
                [28, 27, 26, 25, 22],
                [28, 27, 26, 25, 21],
                [28, 27, 26, 25, 20],
                [28, 27, 26, 25, 19],
                [28, 27, 26, 25, 18],
                [28, 27, 26, 25, 17],
                [28, 27, 26, 25, 16],
                [28, 27, 26, 25, 15],
                [28, 27, 26, 25, 14],
                [28, 27, 26, 25, 13],
                [28, 27, 26, 25, 12],
                [28, 27, 26, 25, 11],
                [28, 27, 26, 25, 10],
                [28, 27, 26, 25, 9],
                [28, 27, 26, 25, 8],
                [28, 27, 26, 25, 7],
                [28, 27, 26, 25, 6],
                [28, 27, 26, 25, 5],
                [28, 27, 26, 25, 4],
                [28, 27, 26, 25, 3],
                [28, 27, 26, 25, 2],
                [28, 27, 26, 25, 1],
                //7:
                [52, 24, 23, 22, 21],
                [51, 24, 23, 22, 21],
                [50, 24, 23, 22, 21],
                [49, 24, 23, 22, 21],
                [48, 24, 23, 22, 21],
                [47, 24, 23, 22, 21],
                [46, 24, 23, 22, 21],
                [45, 24, 23, 22, 21],
                [44, 24, 23, 22, 21],
                [43, 24, 23, 22, 21],
                [42, 24, 23, 22, 21],
                [41, 24, 23, 22, 21],
                [40, 24, 23, 22, 21],
                [39, 24, 23, 22, 21],
                [38, 24, 23, 22, 21],
                [37, 24, 23, 22, 21],
                [36, 24, 23, 22, 21],
                [35, 24, 23, 22, 21],
                [34, 24, 23, 22, 21],
                [33, 24, 23, 22, 21],
                [32, 24, 23, 22, 21],
                [31, 24, 23, 22, 21],
                [30, 24, 23, 22, 21],
                [29, 24, 23, 22, 21],
                [28, 24, 23, 22, 21],
                [27, 24, 23, 22, 21],
                [26, 24, 23, 22, 21],
                [25, 24, 23, 22, 21],
                [24, 23, 22, 21, 20],
                [24, 23, 22, 21, 19],
                [24, 23, 22, 21, 18],
                [24, 23, 22, 21, 17],
                [24, 23, 22, 21, 16],
                [24, 23, 22, 21, 15],
                [24, 23, 22, 21, 14],
                [24, 23, 22, 21, 13],
                [24, 23, 22, 21, 12],
                [24, 23, 22, 21, 11],
                [24, 23, 22, 21, 10],
                [24, 23, 22, 21, 9],
                [24, 23, 22, 21, 8],
                [24, 23, 22, 21, 7],
                [24, 23, 22, 21, 6],
                [24, 23, 22, 21, 5],
                [24, 23, 22, 21, 4],
                [24, 23, 22, 21, 3],
                [24, 23, 22, 21, 2],
                [24, 23, 22, 21, 1],
                //6:
                [52, 20, 19, 18, 17],
                [51, 20, 19, 18, 17],
                [50, 20, 19, 18, 17],
                [49, 20, 19, 18, 17],
                [48, 20, 19, 18, 17],
                [47, 20, 19, 18, 17],
                [46, 20, 19, 18, 17],
                [45, 20, 19, 18, 17],
                [44, 20, 19, 18, 17],
                [43, 20, 19, 18, 17],
                [42, 20, 19, 18, 17],
                [41, 20, 19, 18, 17],
                [40, 20, 19, 18, 17],
                [39, 20, 19, 18, 17],
                [38, 20, 19, 18, 17],
                [37, 20, 19, 18, 17],
                [36, 20, 19, 18, 17],
                [35, 20, 19, 18, 17],
                [34, 20, 19, 18, 17],
                [33, 20, 19, 18, 17],
                [32, 20, 19, 18, 17],
                [31, 20, 19, 18, 17],
                [30, 20, 19, 18, 17],
                [29, 20, 19, 18, 17],
                [28, 20, 19, 18, 17],
                [27, 20, 19, 18, 17],
                [26, 20, 19, 18, 17],
                [25, 20, 19, 18, 17],
                [24, 20, 19, 18, 17],
                [23, 20, 19, 18, 17],
                [22, 20, 19, 18, 17],
                [21, 20, 19, 18, 17],
                [20, 19, 18, 17, 16],
                [20, 19, 18, 17, 15],
                [20, 19, 18, 17, 14],
                [20, 19, 18, 17, 13],
                [20, 19, 18, 17, 12],
                [20, 19, 18, 17, 11],
                [20, 19, 18, 17, 10],
                [20, 19, 18, 17, 9],
                [20, 19, 18, 17, 8],
                [20, 19, 18, 17, 7],
                [20, 19, 18, 17, 6],
                [20, 19, 18, 17, 5],
                [20, 19, 18, 17, 4],
                [20, 19, 18, 17, 3],
                [20, 19, 18, 17, 2],
                [20, 19, 18, 17, 1],
                //5:
                [52, 16, 15, 14, 13],
                [51, 16, 15, 14, 13],
                [50, 16, 15, 14, 13],
                [49, 16, 15, 14, 13],
                [48, 16, 15, 14, 13],
                [47, 16, 15, 14, 13],
                [46, 16, 15, 14, 13],
                [45, 16, 15, 14, 13],
                [44, 16, 15, 14, 13],
                [43, 16, 15, 14, 13],
                [42, 16, 15, 14, 13],
                [41, 16, 15, 14, 13],
                [40, 16, 15, 14, 13],
                [39, 16, 15, 14, 13],
                [38, 16, 15, 14, 13],
                [37, 16, 15, 14, 13],
                [36, 16, 15, 14, 13],
                [35, 16, 15, 14, 13],
                [34, 16, 15, 14, 13],
                [33, 16, 15, 14, 13],
                [32, 16, 15, 14, 13],
                [31, 16, 15, 14, 13],
                [30, 16, 15, 14, 13],
                [29, 16, 15, 14, 13],
                [28, 16, 15, 14, 13],
                [27, 16, 15, 14, 13],
                [26, 16, 15, 14, 13],
                [25, 16, 15, 14, 13],
                [24, 16, 15, 14, 13],
                [23, 16, 15, 14, 13],
                [22, 16, 15, 14, 13],
                [21, 16, 15, 14, 13],
                [20, 16, 15, 14, 13],
                [19, 16, 15, 14, 13],
                [18, 16, 15, 14, 13],
                [17, 16, 15, 14, 13],
                [16, 15, 14, 13, 12],
                [16, 15, 14, 13, 11],
                [16, 15, 14, 13, 10],
                [16, 15, 14, 13, 9],
                [16, 15, 14, 13, 8],
                [16, 15, 14, 13, 7],
                [16, 15, 14, 13, 6],
                [16, 15, 14, 13, 5],
                [16, 15, 14, 13, 4],
                [16, 15, 14, 13, 3],
                [16, 15, 14, 13, 2],
                [16, 15, 14, 13, 1],
                //4:
                [52, 12, 11, 10, 9],
                [51, 12, 11, 10, 9],
                [50, 12, 11, 10, 9],
                [49, 12, 11, 10, 9],
                [48, 12, 11, 10, 9],
                [47, 12, 11, 10, 9],
                [46, 12, 11, 10, 9],
                [45, 12, 11, 10, 9],
                [44, 12, 11, 10, 9],
                [43, 12, 11, 10, 9],
                [42, 12, 11, 10, 9],
                [41, 12, 11, 10, 9],
                [40, 12, 11, 10, 9],
                [39, 12, 11, 10, 9],
                [38, 12, 11, 10, 9],
                [37, 12, 11, 10, 9],
                [36, 12, 11, 10, 9],
                [35, 12, 11, 10, 9],
                [34, 12, 11, 10, 9],
                [33, 12, 11, 10, 9],
                [32, 12, 11, 10, 9],
                [31, 12, 11, 10, 9],
                [30, 12, 11, 10, 9],
                [29, 12, 11, 10, 9],
                [28, 12, 11, 10, 9],
                [27, 12, 11, 10, 9],
                [26, 12, 11, 10, 9],
                [25, 12, 11, 10, 9],
                [24, 12, 11, 10, 9],
                [23, 12, 11, 10, 9],
                [22, 12, 11, 10, 9],
                [21, 12, 11, 10, 9],
                [20, 12, 11, 10, 9],
                [19, 12, 11, 10, 9],
                [18, 12, 11, 10, 9],
                [17, 12, 11, 10, 9],
                [16, 12, 11, 10, 9],
                [15, 12, 11, 10, 9],
                [14, 12, 11, 10, 9],
                [13, 12, 11, 10, 9],
                [12, 11, 10, 9, 8],
                [12, 11, 10, 9, 7],
                [12, 11, 10, 9, 6],
                [12, 11, 10, 9, 5],
                [12, 11, 10, 9, 4],
                [12, 11, 10, 9, 3],
                [12, 11, 10, 9, 2],
                [12, 11, 10, 9, 1],
                //3:
                [52, 8, 7, 6, 5],
                [51, 8, 7, 6, 5],
                [50, 8, 7, 6, 5],
                [49, 8, 7, 6, 5],
                [48, 8, 7, 6, 5],
                [47, 8, 7, 6, 5],
                [46, 8, 7, 6, 5],
                [45, 8, 7, 6, 5],
                [44, 8, 7, 6, 5],
                [43, 8, 7, 6, 5],
                [42, 8, 7, 6, 5],
                [41, 8, 7, 6, 5],
                [40, 8, 7, 6, 5],
                [39, 8, 7, 6, 5],
                [38, 8, 7, 6, 5],
                [37, 8, 7, 6, 5],
                [36, 8, 7, 6, 5],
                [35, 8, 7, 6, 5],
                [34, 8, 7, 6, 5],
                [33, 8, 7, 6, 5],
                [32, 8, 7, 6, 5],
                [31, 8, 7, 6, 5],
                [30, 8, 7, 6, 5],
                [29, 8, 7, 6, 5],
                [28, 8, 7, 6, 5],
                [27, 8, 7, 6, 5],
                [26, 8, 7, 6, 5],
                [25, 8, 7, 6, 5],
                [24, 8, 7, 6, 5],
                [23, 8, 7, 6, 5],
                [22, 8, 7, 6, 5],
                [21, 8, 7, 6, 5],
                [20, 8, 7, 6, 5],
                [19, 8, 7, 6, 5],
                [18, 8, 7, 6, 5],
                [17, 8, 7, 6, 5],
                [16, 8, 7, 6, 5],
                [15, 8, 7, 6, 5],
                [14, 8, 7, 6, 5],
                [13, 8, 7, 6, 5],
                [12, 8, 7, 6, 5],
                [11, 8, 7, 6, 5],
                [10, 8, 7, 6, 5],
                [9, 8, 7, 6, 5],
                [8, 7, 6, 5, 4],
                [8, 7, 6, 5, 3],
                [8, 7, 6, 5, 2],
                [8, 7, 6, 5, 1],
                //2:
                [52, 4, 3, 2, 1],
                [51, 4, 3, 2, 1],
                [50, 4, 3, 2, 1],
                [49, 4, 3, 2, 1],
                [48, 4, 3, 2, 1],
                [47, 4, 3, 2, 1],
                [46, 4, 3, 2, 1],
                [45, 4, 3, 2, 1],
                [44, 4, 3, 2, 1],
                [43, 4, 3, 2, 1],
                [42, 4, 3, 2, 1],
                [41, 4, 3, 2, 1],
                [40, 4, 3, 2, 1],
                [39, 4, 3, 2, 1],
                [38, 4, 3, 2, 1],
                [37, 4, 3, 2, 1],
                [36, 4, 3, 2, 1],
                [35, 4, 3, 2, 1],
                [34, 4, 3, 2, 1],
                [33, 4, 3, 2, 1],
                [32, 4, 3, 2, 1],
                [31, 4, 3, 2, 1],
                [30, 4, 3, 2, 1],
                [29, 4, 3, 2, 1],
                [28, 4, 3, 2, 1],
                [27, 4, 3, 2, 1],
                [26, 4, 3, 2, 1],
                [25, 4, 3, 2, 1],
                [24, 4, 3, 2, 1],
                [23, 4, 3, 2, 1],
                [22, 4, 3, 2, 1],
                [21, 4, 3, 2, 1],
                [20, 4, 3, 2, 1],
                [19, 4, 3, 2, 1],
                [18, 4, 3, 2, 1],
                [17, 4, 3, 2, 1],
                [16, 4, 3, 2, 1],
                [15, 4, 3, 2, 1],
                [14, 4, 3, 2, 1],
                [13, 4, 3, 2, 1],
                [12, 4, 3, 2, 1],
                [11, 4, 3, 2, 1],
                [10, 4, 3, 2, 1],
                [9, 4, 3, 2, 1],
                [8, 4, 3, 2, 1],
                [7, 4, 3, 2, 1],
                [6, 4, 3, 2, 1],
                [5, 4, 3, 2, 1]
            ];

            foreach ($cardTieArray as $key => $item) {
                if ($cardArray == $item) {
                    $isTie = "true";
                    $index = $key;
                }
            }

            // if ($isTie == "true") {
            //     echo "<br/>";
            //     echo "相同";
            //     echo "<br/>";
            // } else {
            //     echo "<br/>";
            //     echo "不相同";
            //     echo "<br/>";
            // }
        } else {
            echo "CARD_LENGTH_NOT_EQUAL_FIVE";
        }

        $returnInfo          = [];
        $returnInfo["flag"]  = $isTie;
        $returnInfo["index"] = $index;

        return $returnInfo;
    }

    /**
     * 是否是葫芦
     */

    /**
     * 获取所有葫芦的情况
     * =============================================================================================================
     * 葫芦的情况是三带二
     * 从任意一个全组里取两个组,分别从组里取3张和两张牌,就组成葫芦
     * 十三个组任意去两组,可以有多少种情况?
     * A,K
     * K,Q
     * Q,J
     * J,10
     * 10,9
     * 9,8
     * 8,7
     * 7,6
     * 6,5
     * 5,4
     * 4,3
     * 3,2
     * A和其他12组,就有12组情况,13*12=156种情况
     * 两个组内,分别取3张和2张,有多少种情况
     * 加设A组,A1,A2,A3,A4,取三张
     * A1,2,3
     * A2,3,4
     * A1,3,4
     * A1,2,4
     * 共四种情况
     * 如果取两张
     * A1,2
     * A1,3,
     * A1,4
     * A2,3
     * A2,4
     * A3,4
     * 共六种情况
     * 取三+取二=4+6
     * 取二+取三=6+4
     * 一共156*10=1560种情况
     */
    // public function getFullThreeSameWithPair() {
    //     //从13组里取出任意两组
    //     $getTwoGroupFromThirteenGroup = self::getTwoGroupFromThirteenGroup();
    //     foreach ($getTwoGroupFromThirteenGroup as $key => $item) {
    //         // echo print_r($item) . "<br/>";
    //         echo "--" . $item[0][0] . "," . $item[0][1] . "," . $item[0][2] . "," . $item[0][3] . "--";
    //         echo "--" . $item[1][0] . "," . $item[1][1] . "," . $item[1][2] . "," . $item[1][3] . "--<br/>";
    //     }
    //     //取出的任意两组中,每组按取2或者取3来随机
    //     //如果AB中,第一个取2 那么第二个取3
    //     //如果第一个取3,那么第二个取2
    //     //如果第一个取2,那么有6种情况,第二个有4中情况,他们分别是
    //     //A1,A2,A3,A4,A5,A6
    //     //B1,B2,B3,B4
    //     //排列组合为:
    //     //[A1,B1],[A1,B2],[A1,B3],[A1,B4]
    //     //[A2,B1],[A2,B2],[A2,B3],[A2,B4]
    //     //[A3,B1],[A3,B2],[A3,B3],[A3,B4]
    //     //[A4,B1],[A4,B2],[A4,B3],[A4,B4]
    //     //[A5,B1],[A5,B2],[A5,B3],[A5,B4]
    //     //[A6,B1],[A6,B2],[A6,B3],[A6,B4]
    //     // 第二种是
    //
    // }

    /**
     * 返回全数组,从A到2
     * 当一组数字进来的时候,给出有几个相同
     */
    function getFullThreeSameWithPair($cardArray) {
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 2) {
            if ((current($samePositionArray) == 1 && end($samePositionArray) == 4) ||
                (current($samePositionArray) == 4 && end($samePositionArray) == 1)
            ) {
                // echo "铁支";
            }

            if ((current($samePositionArray) == 3 && end($samePositionArray) == 2)) {
                // echo "<br/>";
                // echo "葫芦3在前";
                // echo "<br/>";
                //如果3在前,取第一个数字
                $firstNumber         = $cardArray[0];
                $positionInFullArray = $cardPositionArray[$firstNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                // echo "<br/>";
            }

            if ((current($samePositionArray) == 2 && end($samePositionArray) == 3)) {
                // echo "葫芦3在后";
                //如果3在后,取最后一个数字
                $lastNumber          = $cardArray[4];
                $positionInFullArray = $cardPositionArray[$lastNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                // echo "<br/>";
            }
            // if(count($))
        }
    }

    function isFullThreeSameWithPair($cardArray) {
        $flag      = "false";
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 2) {
            if ((current($samePositionArray) == 1 && end($samePositionArray) == 4) ||
                (current($samePositionArray) == 4 && end($samePositionArray) == 1)
            ) {
                // echo "铁支";
            }

            if ((current($samePositionArray) == 3 && end($samePositionArray) == 2)) {
                // echo "<br/>";
                // echo "葫芦3在前";
                // echo "<br/>";
                //如果3在前,取第一个数字
                $firstNumber         = $cardArray[0];
                $positionInFullArray = $cardPositionArray[$firstNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                // echo "<br/>";

                $flag = "true";
            }

            if ((current($samePositionArray) == 2 && end($samePositionArray) == 3)) {
                // echo "葫芦3在后";
                //如果3在后,取最后一个数字
                $lastNumber          = $cardArray[4];
                $positionInFullArray = $cardPositionArray[$lastNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                // echo "<br/>";

                $flag = "true";
            }
            // if(count($))
        }
        return $flag;
    }

    function isFullThreeSameWithPairDetail($cardArray) {
        $flag         = "false";
        $index        = -1;
        $cardPosition = "";
        $fullArray    = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 2) {
            if ((current($samePositionArray) == 1 && end($samePositionArray) == 4) ||
                (current($samePositionArray) == 4 && end($samePositionArray) == 1)
            ) {
                // echo "铁支";
            }

            if ((current($samePositionArray) == 3 && end($samePositionArray) == 2)) {
                // echo "<br/>";
                // echo "葫芦3在前";
                // echo "<br/>";
                //如果3在前,取第一个数字
                $firstNumber         = $cardArray[0];
                $positionInFullArray = $cardPositionArray[$firstNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                $index        = $positionInFullArray;
                $cardPosition = "head";
                // echo "<br/>";

                $flag = "true";
            }

            if ((current($samePositionArray) == 2 && end($samePositionArray) == 3)) {
                echo "葫芦3在后";
                //如果3在后,取最后一个数字
                $lastNumber          = $cardArray[4];
                $positionInFullArray = $cardPositionArray[$lastNumber];
                // echo "在组中的位置:" . $positionInFullArray;
                // echo "<br/>";
                $index        = $positionInFullArray;
                $cardPosition = "end";

                $flag = "true";
            }
            // if(count($))
        }

        $returnInfo             = [];
        $returnInfo["flag"]     = $flag;
        $returnInfo["index"]    = $index;
        $returnInfo["position"] = $cardPosition;

        return $returnInfo;
    }

    /**
     * 从全花色中取三张牌
     *
     * @param $oneArray
     *
     * @return array
     */

    function getThreeCardFromOneArray($oneArray) {
        $returnArray = [];
        if (count($oneArray) == 4) {
            $oneArraySelected = [$oneArray[0], $oneArray[1], $oneArray[2]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[1], $oneArray[2], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[0], $oneArray[2], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[0], $oneArray[1], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
        } else {
            echo "ARRAY_LENGTH_NOT_EQUAL_FOUR";
        }
        return $returnArray;
    }

    /**
     * 从全花色牌中取两张牌
     * A1,2
     * A1,3,
     * A1,4
     * A2,3
     * A2,4
     * A3,4
     * 共六种情况
     */

    function getTwoCardFromOneArray($oneArray) {
        $returnArray = [];
        if (count($oneArray) == 4) {
            $oneArraySelected = [$oneArray[0], $oneArray[1]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[0], $oneArray[2]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[0], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[1], $oneArray[2]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[1], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
            $oneArraySelected = [$oneArray[2], $oneArray[3]];
            array_push($returnArray, $oneArraySelected);
        } else {
            echo "ARRAY_LENGTH_NOT_EQUAL_FOUR";
        }
        return $returnArray;
    }

    /**
     * 从13组里取出任意两组
     */

    function getTwoGroupFromThirteenGroup() {
        $returnArray = [];
        $array       = range(1, 13);
        foreach ($array as $key => $item) {
            $array1 = range(1, 13);
            unset($array1[$key]);
            foreach ($array1 as $key1 => $item1) {
                // echo "*" . self::getOneFromFullGroup($item) . "," . self::getOneFromFullGroup($item1) . "*<br/>";
                $conditionArray = [];
                array_push($conditionArray, getOneFromFullGroup($item));
                array_push($conditionArray, getOneFromFullGroup($item1));
                array_push($returnArray, $conditionArray);
            }
        }

        return $returnArray;
    }

    /**
     * 根据序号从13组牌中获取一组
     */

    function getOneFromFullGroup($index) {
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];
        return $fullArray[$index - 1];
    }

    /**
     * 是否是同花
     */
    function isFullSuit($cardArray) {
        $flag = "false";
        //同花表
        $fullSuitArray = [
            [52, 48, 44, 40, 36, 32, 28, 24, 20, 16, 12, 8, 4], //黑桃
            [51, 47, 43, 39, 35, 31, 27, 23, 19, 15, 11, 7, 3], //红心
            [50, 46, 42, 38, 34, 30, 26, 22, 18, 14, 10, 6, 2], //梅花
            [49, 45, 41, 37, 33, 29, 25, 21, 17, 13, 9, 5, 1] //方块
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullSuitArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 1) {
            if (current($samePositionArray) == 5) {
                // echo "<br/>";
                // echo "是同花,位置是" . current($cardPositionArray);
                // echo "<br/>";
                $flag = "true";
            }
        }
        return $flag;
    }

    /**
     * 是否是同花
     */
    function isFullSuitDetail($cardArray) {
        $flag  = "false";
        $index = -1;
        //同花表
        $fullSuitArray = [
            [52, 48, 44, 40, 36, 32, 28, 24, 20, 16, 12, 8, 4], //黑桃
            [51, 47, 43, 39, 35, 31, 27, 23, 19, 15, 11, 7, 3], //红心
            [50, 46, 42, 38, 34, 30, 26, 22, 18, 14, 10, 6, 2], //梅花
            [49, 45, 41, 37, 33, 29, 25, 21, 17, 13, 9, 5, 1] //方块
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullSuitArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 1) {
            if (current($samePositionArray) == 5) {
                // echo "<br/>";
                // echo "是同花,位置是" . current($cardPositionArray);
                $index = current($cardPositionArray);
                // echo "<br/>";
                $flag = "true";
            }
        }

        $returnInfo          = [];
        $returnInfo["flag"]  = $flag;
        $returnInfo["index"] = $index;
        return $returnInfo;
    }

    /**
     * 是否是顺子
     */
    function isStraight($cardArray) {
        $flag = "false";
        // echo "cardArray" . json_encode($cardArray) . "\n";
        $cardDetailArray = [];
        //将数组转换成带牌值和花色的数组
        foreach ($cardArray as $item) {
            $cardDetail = getCardDetail($item);
            array_push($cardDetailArray, $cardDetail);
        }
        // echo "cardArrayDetail:" . json_encode($cardDetailArray) . "\n";
        if ($cardDetailArray[0][1] == ($cardDetailArray[1][1] + 1) &&
            $cardDetailArray[0][1] == ($cardDetailArray[2][1] + 2) &&
            $cardDetailArray[0][1] == ($cardDetailArray[3][1] + 3) &&
            $cardDetailArray[0][1] == ($cardDetailArray[4][1] + 4)
        ) {
            // echo "<br/>";
            // echo "是顺子";
            // echo "<br/>";
            $flag = "true";
        }

        return $flag;
    }

    /**
     * 带特殊牌型的顺子查询
     *
     * @param $cardArray
     *
     * @return array
     */
    function isStraightDictionaryWithSpecialTypeDetail($cardArray) {
        $isFiveCardStraight = "false";
        $index              = -1;
        rsort($cardArray);

        if (count($cardArray) == 5) {
            $cardStraightArray = [
                [52, 48, 44, 40, 35],
                [52, 48, 44, 40, 34],
                [52, 48, 44, 40, 33],
                [52, 48, 44, 39, 36],
                [52, 48, 44, 39, 35],
                [52, 48, 44, 39, 34],
                [52, 48, 44, 39, 33],
                [52, 48, 44, 38, 36],
                [52, 48, 44, 38, 35],
                [52, 48, 44, 38, 34],
                [52, 48, 44, 38, 33],
                [52, 48, 44, 37, 36],
                [52, 48, 44, 37, 35],
                [52, 48, 44, 37, 34],
                [52, 48, 44, 37, 33],
                [52, 48, 43, 40, 36],
                [52, 48, 43, 40, 35],
                [52, 48, 43, 40, 34],
                [52, 48, 43, 40, 33],
                [52, 48, 43, 39, 36],
                [52, 48, 43, 39, 35],
                [52, 48, 43, 39, 34],
                [52, 48, 43, 39, 33],
                [52, 48, 43, 38, 36],
                [52, 48, 43, 38, 35],
                [52, 48, 43, 38, 34],
                [52, 48, 43, 38, 33],
                [52, 48, 43, 37, 36],
                [52, 48, 43, 37, 35],
                [52, 48, 43, 37, 34],
                [52, 48, 43, 37, 33],
                [52, 48, 42, 40, 36],
                [52, 48, 42, 40, 35],
                [52, 48, 42, 40, 34],
                [52, 48, 42, 40, 33],
                [52, 48, 42, 39, 36],
                [52, 48, 42, 39, 35],
                [52, 48, 42, 39, 34],
                [52, 48, 42, 39, 33],
                [52, 48, 42, 38, 36],
                [52, 48, 42, 38, 35],
                [52, 48, 42, 38, 34],
                [52, 48, 42, 38, 33],
                [52, 48, 42, 37, 36],
                [52, 48, 42, 37, 35],
                [52, 48, 42, 37, 34],
                [52, 48, 42, 37, 33],
                [52, 48, 41, 40, 36],
                [52, 48, 41, 40, 35],
                [52, 48, 41, 40, 34],
                [52, 48, 41, 40, 33],
                [52, 48, 41, 39, 36],
                [52, 48, 41, 39, 35],
                [52, 48, 41, 39, 34],
                [52, 48, 41, 39, 33],
                [52, 48, 41, 38, 36],
                [52, 48, 41, 38, 35],
                [52, 48, 41, 38, 34],
                [52, 48, 41, 38, 33],
                [52, 48, 41, 37, 36],
                [52, 48, 41, 37, 35],
                [52, 48, 41, 37, 34],
                [52, 48, 41, 37, 33],
                [52, 47, 44, 40, 36],
                [52, 47, 44, 40, 35],
                [52, 47, 44, 40, 34],
                [52, 47, 44, 40, 33],
                [52, 47, 44, 39, 36],
                [52, 47, 44, 39, 35],
                [52, 47, 44, 39, 34],
                [52, 47, 44, 39, 33],
                [52, 47, 44, 38, 36],
                [52, 47, 44, 38, 35],
                [52, 47, 44, 38, 34],
                [52, 47, 44, 38, 33],
                [52, 47, 44, 37, 36],
                [52, 47, 44, 37, 35],
                [52, 47, 44, 37, 34],
                [52, 47, 44, 37, 33],
                [52, 47, 43, 40, 36],
                [52, 47, 43, 40, 35],
                [52, 47, 43, 40, 34],
                [52, 47, 43, 40, 33],
                [52, 47, 43, 39, 36],
                [52, 47, 43, 39, 35],
                [52, 47, 43, 39, 34],
                [52, 47, 43, 39, 33],
                [52, 47, 43, 38, 36],
                [52, 47, 43, 38, 35],
                [52, 47, 43, 38, 34],
                [52, 47, 43, 38, 33],
                [52, 47, 43, 37, 36],
                [52, 47, 43, 37, 35],
                [52, 47, 43, 37, 34],
                [52, 47, 43, 37, 33],
                [52, 47, 42, 40, 36],
                [52, 47, 42, 40, 35],
                [52, 47, 42, 40, 34],
                [52, 47, 42, 40, 33],
                [52, 47, 42, 39, 36],
                [52, 47, 42, 39, 35],
                [52, 47, 42, 39, 34],
                [52, 47, 42, 39, 33],
                [52, 47, 42, 38, 36],
                [52, 47, 42, 38, 35],
                [52, 47, 42, 38, 34],
                [52, 47, 42, 38, 33],
                [52, 47, 42, 37, 36],
                [52, 47, 42, 37, 35],
                [52, 47, 42, 37, 34],
                [52, 47, 42, 37, 33],
                [52, 47, 41, 40, 36],
                [52, 47, 41, 40, 35],
                [52, 47, 41, 40, 34],
                [52, 47, 41, 40, 33],
                [52, 47, 41, 39, 36],
                [52, 47, 41, 39, 35],
                [52, 47, 41, 39, 34],
                [52, 47, 41, 39, 33],
                [52, 47, 41, 38, 36],
                [52, 47, 41, 38, 35],
                [52, 47, 41, 38, 34],
                [52, 47, 41, 38, 33],
                [52, 47, 41, 37, 36],
                [52, 47, 41, 37, 35],
                [52, 47, 41, 37, 34],
                [52, 47, 41, 37, 33],
                [52, 46, 44, 40, 36],
                [52, 46, 44, 40, 35],
                [52, 46, 44, 40, 34],
                [52, 46, 44, 40, 33],
                [52, 46, 44, 39, 36],
                [52, 46, 44, 39, 35],
                [52, 46, 44, 39, 34],
                [52, 46, 44, 39, 33],
                [52, 46, 44, 38, 36],
                [52, 46, 44, 38, 35],
                [52, 46, 44, 38, 34],
                [52, 46, 44, 38, 33],
                [52, 46, 44, 37, 36],
                [52, 46, 44, 37, 35],
                [52, 46, 44, 37, 34],
                [52, 46, 44, 37, 33],
                [52, 46, 43, 40, 36],
                [52, 46, 43, 40, 35],
                [52, 46, 43, 40, 34],
                [52, 46, 43, 40, 33],
                [52, 46, 43, 39, 36],
                [52, 46, 43, 39, 35],
                [52, 46, 43, 39, 34],
                [52, 46, 43, 39, 33],
                [52, 46, 43, 38, 36],
                [52, 46, 43, 38, 35],
                [52, 46, 43, 38, 34],
                [52, 46, 43, 38, 33],
                [52, 46, 43, 37, 36],
                [52, 46, 43, 37, 35],
                [52, 46, 43, 37, 34],
                [52, 46, 43, 37, 33],
                [52, 46, 42, 40, 36],
                [52, 46, 42, 40, 35],
                [52, 46, 42, 40, 34],
                [52, 46, 42, 40, 33],
                [52, 46, 42, 39, 36],
                [52, 46, 42, 39, 35],
                [52, 46, 42, 39, 34],
                [52, 46, 42, 39, 33],
                [52, 46, 42, 38, 36],
                [52, 46, 42, 38, 35],
                [52, 46, 42, 38, 34],
                [52, 46, 42, 38, 33],
                [52, 46, 42, 37, 36],
                [52, 46, 42, 37, 35],
                [52, 46, 42, 37, 34],
                [52, 46, 42, 37, 33],
                [52, 46, 41, 40, 36],
                [52, 46, 41, 40, 35],
                [52, 46, 41, 40, 34],
                [52, 46, 41, 40, 33],
                [52, 46, 41, 39, 36],
                [52, 46, 41, 39, 35],
                [52, 46, 41, 39, 34],
                [52, 46, 41, 39, 33],
                [52, 46, 41, 38, 36],
                [52, 46, 41, 38, 35],
                [52, 46, 41, 38, 34],
                [52, 46, 41, 38, 33],
                [52, 46, 41, 37, 36],
                [52, 46, 41, 37, 35],
                [52, 46, 41, 37, 34],
                [52, 46, 41, 37, 33],
                [52, 45, 44, 40, 36],
                [52, 45, 44, 40, 35],
                [52, 45, 44, 40, 34],
                [52, 45, 44, 40, 33],
                [52, 45, 44, 39, 36],
                [52, 45, 44, 39, 35],
                [52, 45, 44, 39, 34],
                [52, 45, 44, 39, 33],
                [52, 45, 44, 38, 36],
                [52, 45, 44, 38, 35],
                [52, 45, 44, 38, 34],
                [52, 45, 44, 38, 33],
                [52, 45, 44, 37, 36],
                [52, 45, 44, 37, 35],
                [52, 45, 44, 37, 34],
                [52, 45, 44, 37, 33],
                [52, 45, 43, 40, 36],
                [52, 45, 43, 40, 35],
                [52, 45, 43, 40, 34],
                [52, 45, 43, 40, 33],
                [52, 45, 43, 39, 36],
                [52, 45, 43, 39, 35],
                [52, 45, 43, 39, 34],
                [52, 45, 43, 39, 33],
                [52, 45, 43, 38, 36],
                [52, 45, 43, 38, 35],
                [52, 45, 43, 38, 34],
                [52, 45, 43, 38, 33],
                [52, 45, 43, 37, 36],
                [52, 45, 43, 37, 35],
                [52, 45, 43, 37, 34],
                [52, 45, 43, 37, 33],
                [52, 45, 42, 40, 36],
                [52, 45, 42, 40, 35],
                [52, 45, 42, 40, 34],
                [52, 45, 42, 40, 33],
                [52, 45, 42, 39, 36],
                [52, 45, 42, 39, 35],
                [52, 45, 42, 39, 34],
                [52, 45, 42, 39, 33],
                [52, 45, 42, 38, 36],
                [52, 45, 42, 38, 35],
                [52, 45, 42, 38, 34],
                [52, 45, 42, 38, 33],
                [52, 45, 42, 37, 36],
                [52, 45, 42, 37, 35],
                [52, 45, 42, 37, 34],
                [52, 45, 42, 37, 33],
                [52, 45, 41, 40, 36],
                [52, 45, 41, 40, 35],
                [52, 45, 41, 40, 34],
                [52, 45, 41, 40, 33],
                [52, 45, 41, 39, 36],
                [52, 45, 41, 39, 35],
                [52, 45, 41, 39, 34],
                [52, 45, 41, 39, 33],
                [52, 45, 41, 38, 36],
                [52, 45, 41, 38, 35],
                [52, 45, 41, 38, 34],
                [52, 45, 41, 38, 33],
                [52, 45, 41, 37, 36],
                [52, 45, 41, 37, 35],
                [52, 45, 41, 37, 34],
                [52, 45, 41, 37, 33],
                [51, 48, 44, 40, 36],
                [51, 48, 44, 40, 35],
                [51, 48, 44, 40, 34],
                [51, 48, 44, 40, 33],
                [51, 48, 44, 39, 36],
                [51, 48, 44, 39, 35],
                [51, 48, 44, 39, 34],
                [51, 48, 44, 39, 33],
                [51, 48, 44, 38, 36],
                [51, 48, 44, 38, 35],
                [51, 48, 44, 38, 34],
                [51, 48, 44, 38, 33],
                [51, 48, 44, 37, 36],
                [51, 48, 44, 37, 35],
                [51, 48, 44, 37, 34],
                [51, 48, 44, 37, 33],
                [51, 48, 43, 40, 36],
                [51, 48, 43, 40, 35],
                [51, 48, 43, 40, 34],
                [51, 48, 43, 40, 33],
                [51, 48, 43, 39, 36],
                [51, 48, 43, 39, 35],
                [51, 48, 43, 39, 34],
                [51, 48, 43, 39, 33],
                [51, 48, 43, 38, 36],
                [51, 48, 43, 38, 35],
                [51, 48, 43, 38, 34],
                [51, 48, 43, 38, 33],
                [51, 48, 43, 37, 36],
                [51, 48, 43, 37, 35],
                [51, 48, 43, 37, 34],
                [51, 48, 43, 37, 33],
                [51, 48, 42, 40, 36],
                [51, 48, 42, 40, 35],
                [51, 48, 42, 40, 34],
                [51, 48, 42, 40, 33],
                [51, 48, 42, 39, 36],
                [51, 48, 42, 39, 35],
                [51, 48, 42, 39, 34],
                [51, 48, 42, 39, 33],
                [51, 48, 42, 38, 36],
                [51, 48, 42, 38, 35],
                [51, 48, 42, 38, 34],
                [51, 48, 42, 38, 33],
                [51, 48, 42, 37, 36],
                [51, 48, 42, 37, 35],
                [51, 48, 42, 37, 34],
                [51, 48, 42, 37, 33],
                [51, 48, 41, 40, 36],
                [51, 48, 41, 40, 35],
                [51, 48, 41, 40, 34],
                [51, 48, 41, 40, 33],
                [51, 48, 41, 39, 36],
                [51, 48, 41, 39, 35],
                [51, 48, 41, 39, 34],
                [51, 48, 41, 39, 33],
                [51, 48, 41, 38, 36],
                [51, 48, 41, 38, 35],
                [51, 48, 41, 38, 34],
                [51, 48, 41, 38, 33],
                [51, 48, 41, 37, 36],
                [51, 48, 41, 37, 35],
                [51, 48, 41, 37, 34],
                [51, 48, 41, 37, 33],
                [51, 47, 44, 40, 36],
                [51, 47, 44, 40, 35],
                [51, 47, 44, 40, 34],
                [51, 47, 44, 40, 33],
                [51, 47, 44, 39, 36],
                [51, 47, 44, 39, 35],
                [51, 47, 44, 39, 34],
                [51, 47, 44, 39, 33],
                [51, 47, 44, 38, 36],
                [51, 47, 44, 38, 35],
                [51, 47, 44, 38, 34],
                [51, 47, 44, 38, 33],
                [51, 47, 44, 37, 36],
                [51, 47, 44, 37, 35],
                [51, 47, 44, 37, 34],
                [51, 47, 44, 37, 33],
                [51, 47, 43, 40, 36],
                [51, 47, 43, 40, 35],
                [51, 47, 43, 40, 34],
                [51, 47, 43, 40, 33],
                [51, 47, 43, 39, 36],
                [51, 47, 43, 39, 34],
                [51, 47, 43, 39, 33],
                [51, 47, 43, 38, 36],
                [51, 47, 43, 38, 35],
                [51, 47, 43, 38, 34],
                [51, 47, 43, 38, 33],
                [51, 47, 43, 37, 36],
                [51, 47, 43, 37, 35],
                [51, 47, 43, 37, 34],
                [51, 47, 43, 37, 33],
                [51, 47, 42, 40, 36],
                [51, 47, 42, 40, 35],
                [51, 47, 42, 40, 34],
                [51, 47, 42, 40, 33],
                [51, 47, 42, 39, 36],
                [51, 47, 42, 39, 35],
                [51, 47, 42, 39, 34],
                [51, 47, 42, 39, 33],
                [51, 47, 42, 38, 36],
                [51, 47, 42, 38, 35],
                [51, 47, 42, 38, 34],
                [51, 47, 42, 38, 33],
                [51, 47, 42, 37, 36],
                [51, 47, 42, 37, 35],
                [51, 47, 42, 37, 34],
                [51, 47, 42, 37, 33],
                [51, 47, 41, 40, 36],
                [51, 47, 41, 40, 35],
                [51, 47, 41, 40, 34],
                [51, 47, 41, 40, 33],
                [51, 47, 41, 39, 36],
                [51, 47, 41, 39, 35],
                [51, 47, 41, 39, 34],
                [51, 47, 41, 39, 33],
                [51, 47, 41, 38, 36],
                [51, 47, 41, 38, 35],
                [51, 47, 41, 38, 34],
                [51, 47, 41, 38, 33],
                [51, 47, 41, 37, 36],
                [51, 47, 41, 37, 35],
                [51, 47, 41, 37, 34],
                [51, 47, 41, 37, 33],
                [51, 46, 44, 40, 36],
                [51, 46, 44, 40, 35],
                [51, 46, 44, 40, 34],
                [51, 46, 44, 40, 33],
                [51, 46, 44, 39, 36],
                [51, 46, 44, 39, 35],
                [51, 46, 44, 39, 34],
                [51, 46, 44, 39, 33],
                [51, 46, 44, 38, 36],
                [51, 46, 44, 38, 35],
                [51, 46, 44, 38, 34],
                [51, 46, 44, 38, 33],
                [51, 46, 44, 37, 36],
                [51, 46, 44, 37, 35],
                [51, 46, 44, 37, 34],
                [51, 46, 44, 37, 33],
                [51, 46, 43, 40, 36],
                [51, 46, 43, 40, 35],
                [51, 46, 43, 40, 34],
                [51, 46, 43, 40, 33],
                [51, 46, 43, 39, 36],
                [51, 46, 43, 39, 35],
                [51, 46, 43, 39, 34],
                [51, 46, 43, 39, 33],
                [51, 46, 43, 38, 36],
                [51, 46, 43, 38, 35],
                [51, 46, 43, 38, 34],
                [51, 46, 43, 38, 33],
                [51, 46, 43, 37, 36],
                [51, 46, 43, 37, 35],
                [51, 46, 43, 37, 34],
                [51, 46, 43, 37, 33],
                [51, 46, 42, 40, 36],
                [51, 46, 42, 40, 35],
                [51, 46, 42, 40, 34],
                [51, 46, 42, 40, 33],
                [51, 46, 42, 39, 36],
                [51, 46, 42, 39, 35],
                [51, 46, 42, 39, 34],
                [51, 46, 42, 39, 33],
                [51, 46, 42, 38, 36],
                [51, 46, 42, 38, 35],
                [51, 46, 42, 38, 34],
                [51, 46, 42, 38, 33],
                [51, 46, 42, 37, 36],
                [51, 46, 42, 37, 35],
                [51, 46, 42, 37, 34],
                [51, 46, 42, 37, 33],
                [51, 46, 41, 40, 36],
                [51, 46, 41, 40, 35],
                [51, 46, 41, 40, 34],
                [51, 46, 41, 40, 33],
                [51, 46, 41, 39, 36],
                [51, 46, 41, 39, 35],
                [51, 46, 41, 39, 34],
                [51, 46, 41, 39, 33],
                [51, 46, 41, 38, 36],
                [51, 46, 41, 38, 35],
                [51, 46, 41, 38, 34],
                [51, 46, 41, 38, 33],
                [51, 46, 41, 37, 36],
                [51, 46, 41, 37, 35],
                [51, 46, 41, 37, 34],
                [51, 46, 41, 37, 33],
                [51, 45, 44, 40, 36],
                [51, 45, 44, 40, 35],
                [51, 45, 44, 40, 34],
                [51, 45, 44, 40, 33],
                [51, 45, 44, 39, 36],
                [51, 45, 44, 39, 35],
                [51, 45, 44, 39, 34],
                [51, 45, 44, 39, 33],
                [51, 45, 44, 38, 36],
                [51, 45, 44, 38, 35],
                [51, 45, 44, 38, 34],
                [51, 45, 44, 38, 33],
                [51, 45, 44, 37, 36],
                [51, 45, 44, 37, 35],
                [51, 45, 44, 37, 34],
                [51, 45, 44, 37, 33],
                [51, 45, 43, 40, 36],
                [51, 45, 43, 40, 35],
                [51, 45, 43, 40, 34],
                [51, 45, 43, 40, 33],
                [51, 45, 43, 39, 36],
                [51, 45, 43, 39, 35],
                [51, 45, 43, 39, 34],
                [51, 45, 43, 39, 33],
                [51, 45, 43, 38, 36],
                [51, 45, 43, 38, 35],
                [51, 45, 43, 38, 34],
                [51, 45, 43, 38, 33],
                [51, 45, 43, 37, 36],
                [51, 45, 43, 37, 35],
                [51, 45, 43, 37, 34],
                [51, 45, 43, 37, 33],
                [51, 45, 42, 40, 36],
                [51, 45, 42, 40, 35],
                [51, 45, 42, 40, 34],
                [51, 45, 42, 40, 33],
                [51, 45, 42, 39, 36],
                [51, 45, 42, 39, 35],
                [51, 45, 42, 39, 34],
                [51, 45, 42, 39, 33],
                [51, 45, 42, 38, 36],
                [51, 45, 42, 38, 35],
                [51, 45, 42, 38, 34],
                [51, 45, 42, 38, 33],
                [51, 45, 42, 37, 36],
                [51, 45, 42, 37, 35],
                [51, 45, 42, 37, 34],
                [51, 45, 42, 37, 33],
                [51, 45, 41, 40, 36],
                [51, 45, 41, 40, 35],
                [51, 45, 41, 40, 34],
                [51, 45, 41, 40, 33],
                [51, 45, 41, 39, 36],
                [51, 45, 41, 39, 35],
                [51, 45, 41, 39, 34],
                [51, 45, 41, 39, 33],
                [51, 45, 41, 38, 36],
                [51, 45, 41, 38, 35],
                [51, 45, 41, 38, 34],
                [51, 45, 41, 38, 33],
                [51, 45, 41, 37, 36],
                [51, 45, 41, 37, 35],
                [51, 45, 41, 37, 34],
                [51, 45, 41, 37, 33],
                [50, 48, 44, 40, 36],
                [50, 48, 44, 40, 35],
                [50, 48, 44, 40, 34],
                [50, 48, 44, 40, 33],
                [50, 48, 44, 39, 36],
                [50, 48, 44, 39, 35],
                [50, 48, 44, 39, 34],
                [50, 48, 44, 39, 33],
                [50, 48, 44, 38, 36],
                [50, 48, 44, 38, 35],
                [50, 48, 44, 38, 34],
                [50, 48, 44, 38, 33],
                [50, 48, 44, 37, 36],
                [50, 48, 44, 37, 35],
                [50, 48, 44, 37, 34],
                [50, 48, 44, 37, 33],
                [50, 48, 43, 40, 36],
                [50, 48, 43, 40, 35],
                [50, 48, 43, 40, 34],
                [50, 48, 43, 40, 33],
                [50, 48, 43, 39, 36],
                [50, 48, 43, 39, 35],
                [50, 48, 43, 39, 34],
                [50, 48, 43, 39, 33],
                [50, 48, 43, 38, 36],
                [50, 48, 43, 38, 35],
                [50, 48, 43, 38, 34],
                [50, 48, 43, 38, 33],
                [50, 48, 43, 37, 36],
                [50, 48, 43, 37, 35],
                [50, 48, 43, 37, 34],
                [50, 48, 43, 37, 33],
                [50, 48, 42, 40, 36],
                [50, 48, 42, 40, 35],
                [50, 48, 42, 40, 34],
                [50, 48, 42, 40, 33],
                [50, 48, 42, 39, 36],
                [50, 48, 42, 39, 35],
                [50, 48, 42, 39, 34],
                [50, 48, 42, 39, 33],
                [50, 48, 42, 38, 36],
                [50, 48, 42, 38, 35],
                [50, 48, 42, 38, 34],
                [50, 48, 42, 38, 33],
                [50, 48, 42, 37, 36],
                [50, 48, 42, 37, 35],
                [50, 48, 42, 37, 34],
                [50, 48, 42, 37, 33],
                [50, 48, 41, 40, 36],
                [50, 48, 41, 40, 35],
                [50, 48, 41, 40, 34],
                [50, 48, 41, 40, 33],
                [50, 48, 41, 39, 36],
                [50, 48, 41, 39, 35],
                [50, 48, 41, 39, 34],
                [50, 48, 41, 39, 33],
                [50, 48, 41, 38, 36],
                [50, 48, 41, 38, 35],
                [50, 48, 41, 38, 34],
                [50, 48, 41, 38, 33],
                [50, 48, 41, 37, 36],
                [50, 48, 41, 37, 35],
                [50, 48, 41, 37, 34],
                [50, 48, 41, 37, 33],
                [50, 47, 44, 40, 36],
                [50, 47, 44, 40, 35],
                [50, 47, 44, 40, 34],
                [50, 47, 44, 40, 33],
                [50, 47, 44, 39, 36],
                [50, 47, 44, 39, 35],
                [50, 47, 44, 39, 34],
                [50, 47, 44, 39, 33],
                [50, 47, 44, 38, 36],
                [50, 47, 44, 38, 35],
                [50, 47, 44, 38, 34],
                [50, 47, 44, 38, 33],
                [50, 47, 44, 37, 36],
                [50, 47, 44, 37, 35],
                [50, 47, 44, 37, 34],
                [50, 47, 44, 37, 33],
                [50, 47, 43, 40, 36],
                [50, 47, 43, 40, 35],
                [50, 47, 43, 40, 34],
                [50, 47, 43, 40, 33],
                [50, 47, 43, 39, 36],
                [50, 47, 43, 39, 35],
                [50, 47, 43, 39, 34],
                [50, 47, 43, 39, 33],
                [50, 47, 43, 38, 36],
                [50, 47, 43, 38, 35],
                [50, 47, 43, 38, 34],
                [50, 47, 43, 38, 33],
                [50, 47, 43, 37, 36],
                [50, 47, 43, 37, 35],
                [50, 47, 43, 37, 34],
                [50, 47, 43, 37, 33],
                [50, 47, 42, 40, 36],
                [50, 47, 42, 40, 35],
                [50, 47, 42, 40, 34],
                [50, 47, 42, 40, 33],
                [50, 47, 42, 39, 36],
                [50, 47, 42, 39, 35],
                [50, 47, 42, 39, 34],
                [50, 47, 42, 39, 33],
                [50, 47, 42, 38, 36],
                [50, 47, 42, 38, 35],
                [50, 47, 42, 38, 34],
                [50, 47, 42, 38, 33],
                [50, 47, 42, 37, 36],
                [50, 47, 42, 37, 35],
                [50, 47, 42, 37, 34],
                [50, 47, 42, 37, 33],
                [50, 47, 41, 40, 36],
                [50, 47, 41, 40, 35],
                [50, 47, 41, 40, 34],
                [50, 47, 41, 40, 33],
                [50, 47, 41, 39, 36],
                [50, 47, 41, 39, 35],
                [50, 47, 41, 39, 34],
                [50, 47, 41, 39, 33],
                [50, 47, 41, 38, 36],
                [50, 47, 41, 38, 35],
                [50, 47, 41, 38, 34],
                [50, 47, 41, 38, 33],
                [50, 47, 41, 37, 36],
                [50, 47, 41, 37, 35],
                [50, 47, 41, 37, 34],
                [50, 47, 41, 37, 33],
                [50, 46, 44, 40, 36],
                [50, 46, 44, 40, 35],
                [50, 46, 44, 40, 34],
                [50, 46, 44, 40, 33],
                [50, 46, 44, 39, 36],
                [50, 46, 44, 39, 35],
                [50, 46, 44, 39, 34],
                [50, 46, 44, 39, 33],
                [50, 46, 44, 38, 36],
                [50, 46, 44, 38, 35],
                [50, 46, 44, 38, 34],
                [50, 46, 44, 38, 33],
                [50, 46, 44, 37, 36],
                [50, 46, 44, 37, 35],
                [50, 46, 44, 37, 34],
                [50, 46, 44, 37, 33],
                [50, 46, 43, 40, 36],
                [50, 46, 43, 40, 35],
                [50, 46, 43, 40, 34],
                [50, 46, 43, 40, 33],
                [50, 46, 43, 39, 36],
                [50, 46, 43, 39, 35],
                [50, 46, 43, 39, 34],
                [50, 46, 43, 39, 33],
                [50, 46, 43, 38, 36],
                [50, 46, 43, 38, 35],
                [50, 46, 43, 38, 34],
                [50, 46, 43, 38, 33],
                [50, 46, 43, 37, 36],
                [50, 46, 43, 37, 35],
                [50, 46, 43, 37, 34],
                [50, 46, 43, 37, 33],
                [50, 46, 42, 40, 36],
                [50, 46, 42, 40, 35],
                [50, 46, 42, 40, 34],
                [50, 46, 42, 40, 33],
                [50, 46, 42, 39, 36],
                [50, 46, 42, 39, 35],
                [50, 46, 42, 39, 34],
                [50, 46, 42, 39, 33],
                [50, 46, 42, 38, 36],
                [50, 46, 42, 38, 35],
                [50, 46, 42, 38, 33],
                [50, 46, 42, 37, 36],
                [50, 46, 42, 37, 35],
                [50, 46, 42, 37, 34],
                [50, 46, 42, 37, 33],
                [50, 46, 41, 40, 36],
                [50, 46, 41, 40, 35],
                [50, 46, 41, 40, 34],
                [50, 46, 41, 40, 33],
                [50, 46, 41, 39, 36],
                [50, 46, 41, 39, 35],
                [50, 46, 41, 39, 34],
                [50, 46, 41, 39, 33],
                [50, 46, 41, 38, 36],
                [50, 46, 41, 38, 35],
                [50, 46, 41, 38, 34],
                [50, 46, 41, 38, 33],
                [50, 46, 41, 37, 36],
                [50, 46, 41, 37, 35],
                [50, 46, 41, 37, 34],
                [50, 46, 41, 37, 33],
                [50, 45, 44, 40, 36],
                [50, 45, 44, 40, 35],
                [50, 45, 44, 40, 34],
                [50, 45, 44, 40, 33],
                [50, 45, 44, 39, 36],
                [50, 45, 44, 39, 35],
                [50, 45, 44, 39, 34],
                [50, 45, 44, 39, 33],
                [50, 45, 44, 38, 36],
                [50, 45, 44, 38, 35],
                [50, 45, 44, 38, 34],
                [50, 45, 44, 38, 33],
                [50, 45, 44, 37, 36],
                [50, 45, 44, 37, 35],
                [50, 45, 44, 37, 34],
                [50, 45, 44, 37, 33],
                [50, 45, 43, 40, 36],
                [50, 45, 43, 40, 35],
                [50, 45, 43, 40, 34],
                [50, 45, 43, 40, 33],
                [50, 45, 43, 39, 36],
                [50, 45, 43, 39, 35],
                [50, 45, 43, 39, 34],
                [50, 45, 43, 39, 33],
                [50, 45, 43, 38, 36],
                [50, 45, 43, 38, 35],
                [50, 45, 43, 38, 34],
                [50, 45, 43, 38, 33],
                [50, 45, 43, 37, 36],
                [50, 45, 43, 37, 35],
                [50, 45, 43, 37, 34],
                [50, 45, 43, 37, 33],
                [50, 45, 42, 40, 36],
                [50, 45, 42, 40, 35],
                [50, 45, 42, 40, 34],
                [50, 45, 42, 40, 33],
                [50, 45, 42, 39, 36],
                [50, 45, 42, 39, 35],
                [50, 45, 42, 39, 34],
                [50, 45, 42, 39, 33],
                [50, 45, 42, 38, 36],
                [50, 45, 42, 38, 35],
                [50, 45, 42, 38, 34],
                [50, 45, 42, 38, 33],
                [50, 45, 42, 37, 36],
                [50, 45, 42, 37, 35],
                [50, 45, 42, 37, 34],
                [50, 45, 42, 37, 33],
                [50, 45, 41, 40, 36],
                [50, 45, 41, 40, 35],
                [50, 45, 41, 40, 34],
                [50, 45, 41, 40, 33],
                [50, 45, 41, 39, 36],
                [50, 45, 41, 39, 35],
                [50, 45, 41, 39, 34],
                [50, 45, 41, 39, 33],
                [50, 45, 41, 38, 36],
                [50, 45, 41, 38, 35],
                [50, 45, 41, 38, 34],
                [50, 45, 41, 38, 33],
                [50, 45, 41, 37, 36],
                [50, 45, 41, 37, 35],
                [50, 45, 41, 37, 34],
                [50, 45, 41, 37, 33],
                [49, 48, 44, 40, 36],
                [49, 48, 44, 40, 35],
                [49, 48, 44, 40, 34],
                [49, 48, 44, 40, 33],
                [49, 48, 44, 39, 36],
                [49, 48, 44, 39, 35],
                [49, 48, 44, 39, 34],
                [49, 48, 44, 39, 33],
                [49, 48, 44, 38, 36],
                [49, 48, 44, 38, 35],
                [49, 48, 44, 38, 34],
                [49, 48, 44, 38, 33],
                [49, 48, 44, 37, 36],
                [49, 48, 44, 37, 35],
                [49, 48, 44, 37, 34],
                [49, 48, 44, 37, 33],
                [49, 48, 43, 40, 36],
                [49, 48, 43, 40, 35],
                [49, 48, 43, 40, 34],
                [49, 48, 43, 40, 33],
                [49, 48, 43, 39, 36],
                [49, 48, 43, 39, 35],
                [49, 48, 43, 39, 34],
                [49, 48, 43, 39, 33],
                [49, 48, 43, 38, 36],
                [49, 48, 43, 38, 35],
                [49, 48, 43, 38, 34],
                [49, 48, 43, 38, 33],
                [49, 48, 43, 37, 36],
                [49, 48, 43, 37, 35],
                [49, 48, 43, 37, 34],
                [49, 48, 43, 37, 33],
                [49, 48, 42, 40, 36],
                [49, 48, 42, 40, 35],
                [49, 48, 42, 40, 34],
                [49, 48, 42, 40, 33],
                [49, 48, 42, 39, 36],
                [49, 48, 42, 39, 35],
                [49, 48, 42, 39, 34],
                [49, 48, 42, 39, 33],
                [49, 48, 42, 38, 36],
                [49, 48, 42, 38, 35],
                [49, 48, 42, 38, 34],
                [49, 48, 42, 38, 33],
                [49, 48, 42, 37, 36],
                [49, 48, 42, 37, 35],
                [49, 48, 42, 37, 34],
                [49, 48, 42, 37, 33],
                [49, 48, 41, 40, 36],
                [49, 48, 41, 40, 35],
                [49, 48, 41, 40, 34],
                [49, 48, 41, 40, 33],
                [49, 48, 41, 39, 36],
                [49, 48, 41, 39, 35],
                [49, 48, 41, 39, 34],
                [49, 48, 41, 39, 33],
                [49, 48, 41, 38, 36],
                [49, 48, 41, 38, 35],
                [49, 48, 41, 38, 34],
                [49, 48, 41, 38, 33],
                [49, 48, 41, 37, 36],
                [49, 48, 41, 37, 35],
                [49, 48, 41, 37, 34],
                [49, 48, 41, 37, 33],
                [49, 47, 44, 40, 36],
                [49, 47, 44, 40, 35],
                [49, 47, 44, 40, 34],
                [49, 47, 44, 40, 33],
                [49, 47, 44, 39, 36],
                [49, 47, 44, 39, 35],
                [49, 47, 44, 39, 34],
                [49, 47, 44, 39, 33],
                [49, 47, 44, 38, 36],
                [49, 47, 44, 38, 35],
                [49, 47, 44, 38, 34],
                [49, 47, 44, 38, 33],
                [49, 47, 44, 37, 36],
                [49, 47, 44, 37, 35],
                [49, 47, 44, 37, 34],
                [49, 47, 44, 37, 33],
                [49, 47, 43, 40, 36],
                [49, 47, 43, 40, 35],
                [49, 47, 43, 40, 34],
                [49, 47, 43, 40, 33],
                [49, 47, 43, 39, 36],
                [49, 47, 43, 39, 35],
                [49, 47, 43, 39, 34],
                [49, 47, 43, 39, 33],
                [49, 47, 43, 38, 36],
                [49, 47, 43, 38, 35],
                [49, 47, 43, 38, 34],
                [49, 47, 43, 38, 33],
                [49, 47, 43, 37, 36],
                [49, 47, 43, 37, 35],
                [49, 47, 43, 37, 34],
                [49, 47, 43, 37, 33],
                [49, 47, 42, 40, 36],
                [49, 47, 42, 40, 35],
                [49, 47, 42, 40, 34],
                [49, 47, 42, 40, 33],
                [49, 47, 42, 39, 36],
                [49, 47, 42, 39, 35],
                [49, 47, 42, 39, 34],
                [49, 47, 42, 39, 33],
                [49, 47, 42, 38, 36],
                [49, 47, 42, 38, 35],
                [49, 47, 42, 38, 34],
                [49, 47, 42, 38, 33],
                [49, 47, 42, 37, 36],
                [49, 47, 42, 37, 35],
                [49, 47, 42, 37, 34],
                [49, 47, 42, 37, 33],
                [49, 47, 41, 40, 36],
                [49, 47, 41, 40, 35],
                [49, 47, 41, 40, 34],
                [49, 47, 41, 40, 33],
                [49, 47, 41, 39, 36],
                [49, 47, 41, 39, 35],
                [49, 47, 41, 39, 34],
                [49, 47, 41, 39, 33],
                [49, 47, 41, 38, 36],
                [49, 47, 41, 38, 35],
                [49, 47, 41, 38, 34],
                [49, 47, 41, 38, 33],
                [49, 47, 41, 37, 36],
                [49, 47, 41, 37, 35],
                [49, 47, 41, 37, 34],
                [49, 47, 41, 37, 33],
                [49, 46, 44, 40, 36],
                [49, 46, 44, 40, 35],
                [49, 46, 44, 40, 34],
                [49, 46, 44, 40, 33],
                [49, 46, 44, 39, 36],
                [49, 46, 44, 39, 35],
                [49, 46, 44, 39, 34],
                [49, 46, 44, 39, 33],
                [49, 46, 44, 38, 36],
                [49, 46, 44, 38, 35],
                [49, 46, 44, 38, 34],
                [49, 46, 44, 38, 33],
                [49, 46, 44, 37, 36],
                [49, 46, 44, 37, 35],
                [49, 46, 44, 37, 34],
                [49, 46, 44, 37, 33],
                [49, 46, 43, 40, 36],
                [49, 46, 43, 40, 35],
                [49, 46, 43, 40, 34],
                [49, 46, 43, 40, 33],
                [49, 46, 43, 39, 36],
                [49, 46, 43, 39, 35],
                [49, 46, 43, 39, 34],
                [49, 46, 43, 39, 33],
                [49, 46, 43, 38, 36],
                [49, 46, 43, 38, 35],
                [49, 46, 43, 38, 34],
                [49, 46, 43, 38, 33],
                [49, 46, 43, 37, 36],
                [49, 46, 43, 37, 35],
                [49, 46, 43, 37, 34],
                [49, 46, 43, 37, 33],
                [49, 46, 42, 40, 36],
                [49, 46, 42, 40, 35],
                [49, 46, 42, 40, 34],
                [49, 46, 42, 40, 33],
                [49, 46, 42, 39, 36],
                [49, 46, 42, 39, 35],
                [49, 46, 42, 39, 34],
                [49, 46, 42, 39, 33],
                [49, 46, 42, 38, 36],
                [49, 46, 42, 38, 35],
                [49, 46, 42, 38, 34],
                [49, 46, 42, 38, 33],
                [49, 46, 42, 37, 36],
                [49, 46, 42, 37, 35],
                [49, 46, 42, 37, 34],
                [49, 46, 42, 37, 33],
                [49, 46, 41, 40, 36],
                [49, 46, 41, 40, 35],
                [49, 46, 41, 40, 34],
                [49, 46, 41, 40, 33],
                [49, 46, 41, 39, 36],
                [49, 46, 41, 39, 35],
                [49, 46, 41, 39, 34],
                [49, 46, 41, 39, 33],
                [49, 46, 41, 38, 36],
                [49, 46, 41, 38, 35],
                [49, 46, 41, 38, 34],
                [49, 46, 41, 38, 33],
                [49, 46, 41, 37, 36],
                [49, 46, 41, 37, 35],
                [49, 46, 41, 37, 34],
                [49, 46, 41, 37, 33],
                [49, 45, 44, 40, 36],
                [49, 45, 44, 40, 35],
                [49, 45, 44, 40, 34],
                [49, 45, 44, 40, 33],
                [49, 45, 44, 39, 36],
                [49, 45, 44, 39, 35],
                [49, 45, 44, 39, 34],
                [49, 45, 44, 39, 33],
                [49, 45, 44, 38, 36],
                [49, 45, 44, 38, 35],
                [49, 45, 44, 38, 34],
                [49, 45, 44, 38, 33],
                [49, 45, 44, 37, 36],
                [49, 45, 44, 37, 35],
                [49, 45, 44, 37, 34],
                [49, 45, 44, 37, 33],
                [49, 45, 43, 40, 36],
                [49, 45, 43, 40, 35],
                [49, 45, 43, 40, 34],
                [49, 45, 43, 40, 33],
                [49, 45, 43, 39, 36],
                [49, 45, 43, 39, 35],
                [49, 45, 43, 39, 34],
                [49, 45, 43, 39, 33],
                [49, 45, 43, 38, 36],
                [49, 45, 43, 38, 35],
                [49, 45, 43, 38, 34],
                [49, 45, 43, 38, 33],
                [49, 45, 43, 37, 36],
                [49, 45, 43, 37, 35],
                [49, 45, 43, 37, 34],
                [49, 45, 43, 37, 33],
                [49, 45, 42, 40, 36],
                [49, 45, 42, 40, 35],
                [49, 45, 42, 40, 34],
                [49, 45, 42, 40, 33],
                [49, 45, 42, 39, 36],
                [49, 45, 42, 39, 35],
                [49, 45, 42, 39, 34],
                [49, 45, 42, 39, 33],
                [49, 45, 42, 38, 36],
                [49, 45, 42, 38, 35],
                [49, 45, 42, 38, 34],
                [49, 45, 42, 38, 33],
                [49, 45, 42, 37, 36],
                [49, 45, 42, 37, 35],
                [49, 45, 42, 37, 34],
                [49, 45, 42, 37, 33],
                [49, 45, 41, 40, 36],
                [49, 45, 41, 40, 35],
                [49, 45, 41, 40, 34],
                [49, 45, 41, 40, 33],
                [49, 45, 41, 39, 36],
                [49, 45, 41, 39, 35],
                [49, 45, 41, 39, 34],
                [49, 45, 41, 39, 33],
                [49, 45, 41, 38, 36],
                [49, 45, 41, 38, 35],
                [49, 45, 41, 38, 34],
                [49, 45, 41, 38, 33],
                [49, 45, 41, 37, 36],
                [49, 45, 41, 37, 35],
                [49, 45, 41, 37, 34],
                //------------------------- 特殊牌型 开始 -----------------------
                //A,5,4,3,2 ---> A 2 3 4 5
                [52, 16, 12, 8, 3],
                [52, 16, 12, 8, 2],
                [52, 16, 12, 8, 1],
                [52, 16, 12, 7, 4],
                [52, 16, 12, 7, 3],
                [52, 16, 12, 7, 2],
                [52, 16, 12, 7, 1],
                [52, 16, 12, 6, 4],
                [52, 16, 12, 6, 3],
                [52, 16, 12, 6, 2],
                [52, 16, 12, 6, 1],
                [52, 16, 12, 5, 4],
                [52, 16, 12, 5, 3],
                [52, 16, 12, 5, 2],
                [52, 16, 12, 5, 1],
                [52, 16, 11, 8, 4],
                [52, 16, 11, 8, 3],
                [52, 16, 11, 8, 2],
                [52, 16, 11, 8, 1],
                [52, 16, 11, 7, 4],
                [52, 16, 11, 7, 3],
                [52, 16, 11, 7, 2],
                [52, 16, 11, 7, 1],
                [52, 16, 11, 6, 4],
                [52, 16, 11, 6, 3],
                [52, 16, 11, 6, 2],
                [52, 16, 11, 6, 1],
                [52, 16, 11, 5, 4],
                [52, 16, 11, 5, 3],
                [52, 16, 11, 5, 2],
                [52, 16, 11, 5, 1],
                [52, 16, 10, 8, 4],
                [52, 16, 10, 8, 3],
                [52, 16, 10, 8, 2],
                [52, 16, 10, 8, 1],
                [52, 16, 10, 7, 4],
                [52, 16, 10, 7, 3],
                [52, 16, 10, 7, 2],
                [52, 16, 10, 7, 1],
                [52, 16, 10, 6, 4],
                [52, 16, 10, 6, 3],
                [52, 16, 10, 6, 2],
                [52, 16, 10, 6, 1],
                [52, 16, 10, 5, 4],
                [52, 16, 10, 5, 3],
                [52, 16, 10, 5, 2],
                [52, 16, 10, 5, 1],
                [52, 16, 9, 8, 4],
                [52, 16, 9, 8, 3],
                [52, 16, 9, 8, 2],
                [52, 16, 9, 8, 1],
                [52, 16, 9, 7, 4],
                [52, 16, 9, 7, 3],
                [52, 16, 9, 7, 2],
                [52, 16, 9, 7, 1],
                [52, 16, 9, 6, 4],
                [52, 16, 9, 6, 3],
                [52, 16, 9, 6, 2],
                [52, 16, 9, 6, 1],
                [52, 16, 9, 5, 4],
                [52, 16, 9, 5, 3],
                [52, 16, 9, 5, 2],
                [52, 16, 9, 5, 1],
                [52, 15, 12, 8, 4],
                [52, 15, 12, 8, 3],
                [52, 15, 12, 8, 2],
                [52, 15, 12, 8, 1],
                [52, 15, 12, 7, 4],
                [52, 15, 12, 7, 3],
                [52, 15, 12, 7, 2],
                [52, 15, 12, 7, 1],
                [52, 15, 12, 6, 4],
                [52, 15, 12, 6, 3],
                [52, 15, 12, 6, 2],
                [52, 15, 12, 6, 1],
                [52, 15, 12, 5, 4],
                [52, 15, 12, 5, 3],
                [52, 15, 12, 5, 2],
                [52, 15, 12, 5, 1],
                [52, 15, 11, 8, 4],
                [52, 15, 11, 8, 3],
                [52, 15, 11, 8, 2],
                [52, 15, 11, 8, 1],
                [52, 15, 11, 7, 4],
                [52, 15, 11, 7, 3],
                [52, 15, 11, 7, 2],
                [52, 15, 11, 7, 1],
                [52, 15, 11, 6, 4],
                [52, 15, 11, 6, 3],
                [52, 15, 11, 6, 2],
                [52, 15, 11, 6, 1],
                [52, 15, 11, 5, 4],
                [52, 15, 11, 5, 3],
                [52, 15, 11, 5, 2],
                [52, 15, 11, 5, 1],
                [52, 15, 10, 8, 4],
                [52, 15, 10, 8, 3],
                [52, 15, 10, 8, 2],
                [52, 15, 10, 8, 1],
                [52, 15, 10, 7, 4],
                [52, 15, 10, 7, 3],
                [52, 15, 10, 7, 2],
                [52, 15, 10, 7, 1],
                [52, 15, 10, 6, 4],
                [52, 15, 10, 6, 3],
                [52, 15, 10, 6, 2],
                [52, 15, 10, 6, 1],
                [52, 15, 10, 5, 4],
                [52, 15, 10, 5, 3],
                [52, 15, 10, 5, 2],
                [52, 15, 10, 5, 1],
                [52, 15, 9, 8, 4],
                [52, 15, 9, 8, 3],
                [52, 15, 9, 8, 2],
                [52, 15, 9, 8, 1],
                [52, 15, 9, 7, 4],
                [52, 15, 9, 7, 3],
                [52, 15, 9, 7, 2],
                [52, 15, 9, 7, 1],
                [52, 15, 9, 6, 4],
                [52, 15, 9, 6, 3],
                [52, 15, 9, 6, 2],
                [52, 15, 9, 6, 1],
                [52, 15, 9, 5, 4],
                [52, 15, 9, 5, 3],
                [52, 15, 9, 5, 2],
                [52, 15, 9, 5, 1],
                [52, 14, 12, 8, 4],
                [52, 14, 12, 8, 3],
                [52, 14, 12, 8, 2],
                [52, 14, 12, 8, 1],
                [52, 14, 12, 7, 4],
                [52, 14, 12, 7, 3],
                [52, 14, 12, 7, 2],
                [52, 14, 12, 7, 1],
                [52, 14, 12, 6, 4],
                [52, 14, 12, 6, 3],
                [52, 14, 12, 6, 2],
                [52, 14, 12, 6, 1],
                [52, 14, 12, 5, 4],
                [52, 14, 12, 5, 3],
                [52, 14, 12, 5, 2],
                [52, 14, 12, 5, 1],
                [52, 14, 11, 8, 4],
                [52, 14, 11, 8, 3],
                [52, 14, 11, 8, 2],
                [52, 14, 11, 8, 1],
                [52, 14, 11, 7, 4],
                [52, 14, 11, 7, 3],
                [52, 14, 11, 7, 2],
                [52, 14, 11, 7, 1],
                [52, 14, 11, 6, 4],
                [52, 14, 11, 6, 3],
                [52, 14, 11, 6, 2],
                [52, 14, 11, 6, 1],
                [52, 14, 11, 5, 4],
                [52, 14, 11, 5, 3],
                [52, 14, 11, 5, 2],
                [52, 14, 11, 5, 1],
                [52, 14, 10, 8, 4],
                [52, 14, 10, 8, 3],
                [52, 14, 10, 8, 2],
                [52, 14, 10, 8, 1],
                [52, 14, 10, 7, 4],
                [52, 14, 10, 7, 3],
                [52, 14, 10, 7, 2],
                [52, 14, 10, 7, 1],
                [52, 14, 10, 6, 4],
                [52, 14, 10, 6, 3],
                [52, 14, 10, 6, 2],
                [52, 14, 10, 6, 1],
                [52, 14, 10, 5, 4],
                [52, 14, 10, 5, 3],
                [52, 14, 10, 5, 2],
                [52, 14, 10, 5, 1],
                [52, 14, 9, 8, 4],
                [52, 14, 9, 8, 3],
                [52, 14, 9, 8, 2],
                [52, 14, 9, 8, 1],
                [52, 14, 9, 7, 4],
                [52, 14, 9, 7, 3],
                [52, 14, 9, 7, 2],
                [52, 14, 9, 7, 1],
                [52, 14, 9, 6, 4],
                [52, 14, 9, 6, 3],
                [52, 14, 9, 6, 2],
                [52, 14, 9, 6, 1],
                [52, 14, 9, 5, 4],
                [52, 14, 9, 5, 3],
                [52, 14, 9, 5, 2],
                [52, 14, 9, 5, 1],
                [52, 13, 12, 8, 4],
                [52, 13, 12, 8, 3],
                [52, 13, 12, 8, 2],
                [52, 13, 12, 8, 1],
                [52, 13, 12, 7, 4],
                [52, 13, 12, 7, 3],
                [52, 13, 12, 7, 2],
                [52, 13, 12, 7, 1],
                [52, 13, 12, 6, 4],
                [52, 13, 12, 6, 3],
                [52, 13, 12, 6, 2],
                [52, 13, 12, 6, 1],
                [52, 13, 12, 5, 4],
                [52, 13, 12, 5, 3],
                [52, 13, 12, 5, 2],
                [52, 13, 12, 5, 1],
                [52, 13, 11, 8, 4],
                [52, 13, 11, 8, 3],
                [52, 13, 11, 8, 2],
                [52, 13, 11, 8, 1],
                [52, 13, 11, 7, 4],
                [52, 13, 11, 7, 3],
                [52, 13, 11, 7, 2],
                [52, 13, 11, 7, 1],
                [52, 13, 11, 6, 4],
                [52, 13, 11, 6, 3],
                [52, 13, 11, 6, 2],
                [52, 13, 11, 6, 1],
                [52, 13, 11, 5, 4],
                [52, 13, 11, 5, 3],
                [52, 13, 11, 5, 2],
                [52, 13, 11, 5, 1],
                [52, 13, 10, 8, 4],
                [52, 13, 10, 8, 3],
                [52, 13, 10, 8, 2],
                [52, 13, 10, 8, 1],
                [52, 13, 10, 7, 4],
                [52, 13, 10, 7, 3],
                [52, 13, 10, 7, 2],
                [52, 13, 10, 7, 1],
                [52, 13, 10, 6, 4],
                [52, 13, 10, 6, 3],
                [52, 13, 10, 6, 2],
                [52, 13, 10, 6, 1],
                [52, 13, 10, 5, 4],
                [52, 13, 10, 5, 3],
                [52, 13, 10, 5, 2],
                [52, 13, 10, 5, 1],
                [52, 13, 9, 8, 4],
                [52, 13, 9, 8, 3],
                [52, 13, 9, 8, 2],
                [52, 13, 9, 8, 1],
                [52, 13, 9, 7, 4],
                [52, 13, 9, 7, 3],
                [52, 13, 9, 7, 2],
                [52, 13, 9, 7, 1],
                [52, 13, 9, 6, 4],
                [52, 13, 9, 6, 3],
                [52, 13, 9, 6, 2],
                [52, 13, 9, 6, 1],
                [52, 13, 9, 5, 4],
                [52, 13, 9, 5, 3],
                [52, 13, 9, 5, 2],
                [52, 13, 9, 5, 1],
                [51, 16, 12, 8, 4],
                [51, 16, 12, 8, 3],
                [51, 16, 12, 8, 2],
                [51, 16, 12, 8, 1],
                [51, 16, 12, 7, 4],
                [51, 16, 12, 7, 3],
                [51, 16, 12, 7, 2],
                [51, 16, 12, 7, 1],
                [51, 16, 12, 6, 4],
                [51, 16, 12, 6, 3],
                [51, 16, 12, 6, 2],
                [51, 16, 12, 6, 1],
                [51, 16, 12, 5, 4],
                [51, 16, 12, 5, 3],
                [51, 16, 12, 5, 2],
                [51, 16, 12, 5, 1],
                [51, 16, 11, 8, 4],
                [51, 16, 11, 8, 3],
                [51, 16, 11, 8, 2],
                [51, 16, 11, 8, 1],
                [51, 16, 11, 7, 4],
                [51, 16, 11, 7, 3],
                [51, 16, 11, 7, 2],
                [51, 16, 11, 7, 1],
                [51, 16, 11, 6, 4],
                [51, 16, 11, 6, 3],
                [51, 16, 11, 6, 2],
                [51, 16, 11, 6, 1],
                [51, 16, 11, 5, 4],
                [51, 16, 11, 5, 3],
                [51, 16, 11, 5, 2],
                [51, 16, 11, 5, 1],
                [51, 16, 10, 8, 4],
                [51, 16, 10, 8, 3],
                [51, 16, 10, 8, 2],
                [51, 16, 10, 8, 1],
                [51, 16, 10, 7, 4],
                [51, 16, 10, 7, 3],
                [51, 16, 10, 7, 2],
                [51, 16, 10, 7, 1],
                [51, 16, 10, 6, 4],
                [51, 16, 10, 6, 3],
                [51, 16, 10, 6, 2],
                [51, 16, 10, 6, 1],
                [51, 16, 10, 5, 4],
                [51, 16, 10, 5, 3],
                [51, 16, 10, 5, 2],
                [51, 16, 10, 5, 1],
                [51, 16, 9, 8, 4],
                [51, 16, 9, 8, 3],
                [51, 16, 9, 8, 2],
                [51, 16, 9, 8, 1],
                [51, 16, 9, 7, 4],
                [51, 16, 9, 7, 3],
                [51, 16, 9, 7, 2],
                [51, 16, 9, 7, 1],
                [51, 16, 9, 6, 4],
                [51, 16, 9, 6, 3],
                [51, 16, 9, 6, 2],
                [51, 16, 9, 6, 1],
                [51, 16, 9, 5, 4],
                [51, 16, 9, 5, 3],
                [51, 16, 9, 5, 2],
                [51, 16, 9, 5, 1],
                [51, 15, 12, 8, 4],
                [51, 15, 12, 8, 3],
                [51, 15, 12, 8, 2],
                [51, 15, 12, 8, 1],
                [51, 15, 12, 7, 4],
                [51, 15, 12, 7, 3],
                [51, 15, 12, 7, 2],
                [51, 15, 12, 7, 1],
                [51, 15, 12, 6, 4],
                [51, 15, 12, 6, 3],
                [51, 15, 12, 6, 2],
                [51, 15, 12, 6, 1],
                [51, 15, 12, 5, 4],
                [51, 15, 12, 5, 3],
                [51, 15, 12, 5, 2],
                [51, 15, 12, 5, 1],
                [51, 15, 11, 8, 4],
                [51, 15, 11, 8, 3],
                [51, 15, 11, 8, 2],
                [51, 15, 11, 8, 1],
                [51, 15, 11, 7, 4],
                [51, 15, 11, 7, 2],
                [51, 15, 11, 7, 1],
                [51, 15, 11, 6, 4],
                [51, 15, 11, 6, 3],
                [51, 15, 11, 6, 2],
                [51, 15, 11, 6, 1],
                [51, 15, 11, 5, 4],
                [51, 15, 11, 5, 3],
                [51, 15, 11, 5, 2],
                [51, 15, 11, 5, 1],
                [51, 15, 10, 8, 4],
                [51, 15, 10, 8, 3],
                [51, 15, 10, 8, 2],
                [51, 15, 10, 8, 1],
                [51, 15, 10, 7, 4],
                [51, 15, 10, 7, 3],
                [51, 15, 10, 7, 2],
                [51, 15, 10, 7, 1],
                [51, 15, 10, 6, 4],
                [51, 15, 10, 6, 3],
                [51, 15, 10, 6, 2],
                [51, 15, 10, 6, 1],
                [51, 15, 10, 5, 4],
                [51, 15, 10, 5, 3],
                [51, 15, 10, 5, 2],
                [51, 15, 10, 5, 1],
                [51, 15, 9, 8, 4],
                [51, 15, 9, 8, 3],
                [51, 15, 9, 8, 2],
                [51, 15, 9, 8, 1],
                [51, 15, 9, 7, 4],
                [51, 15, 9, 7, 3],
                [51, 15, 9, 7, 2],
                [51, 15, 9, 7, 1],
                [51, 15, 9, 6, 4],
                [51, 15, 9, 6, 3],
                [51, 15, 9, 6, 2],
                [51, 15, 9, 6, 1],
                [51, 15, 9, 5, 4],
                [51, 15, 9, 5, 3],
                [51, 15, 9, 5, 2],
                [51, 15, 9, 5, 1],
                [51, 14, 12, 8, 4],
                [51, 14, 12, 8, 3],
                [51, 14, 12, 8, 2],
                [51, 14, 12, 8, 1],
                [51, 14, 12, 7, 4],
                [51, 14, 12, 7, 3],
                [51, 14, 12, 7, 2],
                [51, 14, 12, 7, 1],
                [51, 14, 12, 6, 4],
                [51, 14, 12, 6, 3],
                [51, 14, 12, 6, 2],
                [51, 14, 12, 6, 1],
                [51, 14, 12, 5, 4],
                [51, 14, 12, 5, 3],
                [51, 14, 12, 5, 2],
                [51, 14, 12, 5, 1],
                [51, 14, 11, 8, 4],
                [51, 14, 11, 8, 3],
                [51, 14, 11, 8, 2],
                [51, 14, 11, 8, 1],
                [51, 14, 11, 7, 4],
                [51, 14, 11, 7, 3],
                [51, 14, 11, 7, 2],
                [51, 14, 11, 7, 1],
                [51, 14, 11, 6, 4],
                [51, 14, 11, 6, 3],
                [51, 14, 11, 6, 2],
                [51, 14, 11, 6, 1],
                [51, 14, 11, 5, 4],
                [51, 14, 11, 5, 3],
                [51, 14, 11, 5, 2],
                [51, 14, 11, 5, 1],
                [51, 14, 10, 8, 4],
                [51, 14, 10, 8, 3],
                [51, 14, 10, 8, 2],
                [51, 14, 10, 8, 1],
                [51, 14, 10, 7, 4],
                [51, 14, 10, 7, 3],
                [51, 14, 10, 7, 2],
                [51, 14, 10, 7, 1],
                [51, 14, 10, 6, 4],
                [51, 14, 10, 6, 3],
                [51, 14, 10, 6, 2],
                [51, 14, 10, 6, 1],
                [51, 14, 10, 5, 4],
                [51, 14, 10, 5, 3],
                [51, 14, 10, 5, 2],
                [51, 14, 10, 5, 1],
                [51, 14, 9, 8, 4],
                [51, 14, 9, 8, 3],
                [51, 14, 9, 8, 2],
                [51, 14, 9, 8, 1],
                [51, 14, 9, 7, 4],
                [51, 14, 9, 7, 3],
                [51, 14, 9, 7, 2],
                [51, 14, 9, 7, 1],
                [51, 14, 9, 6, 4],
                [51, 14, 9, 6, 3],
                [51, 14, 9, 6, 2],
                [51, 14, 9, 6, 1],
                [51, 14, 9, 5, 4],
                [51, 14, 9, 5, 3],
                [51, 14, 9, 5, 2],
                [51, 14, 9, 5, 1],
                [51, 13, 12, 8, 4],
                [51, 13, 12, 8, 3],
                [51, 13, 12, 8, 2],
                [51, 13, 12, 8, 1],
                [51, 13, 12, 7, 4],
                [51, 13, 12, 7, 3],
                [51, 13, 12, 7, 2],
                [51, 13, 12, 7, 1],
                [51, 13, 12, 6, 4],
                [51, 13, 12, 6, 3],
                [51, 13, 12, 6, 2],
                [51, 13, 12, 6, 1],
                [51, 13, 12, 5, 4],
                [51, 13, 12, 5, 3],
                [51, 13, 12, 5, 2],
                [51, 13, 12, 5, 1],
                [51, 13, 11, 8, 4],
                [51, 13, 11, 8, 3],
                [51, 13, 11, 8, 2],
                [51, 13, 11, 8, 1],
                [51, 13, 11, 7, 4],
                [51, 13, 11, 7, 3],
                [51, 13, 11, 7, 2],
                [51, 13, 11, 7, 1],
                [51, 13, 11, 6, 4],
                [51, 13, 11, 6, 3],
                [51, 13, 11, 6, 2],
                [51, 13, 11, 6, 1],
                [51, 13, 11, 5, 4],
                [51, 13, 11, 5, 3],
                [51, 13, 11, 5, 2],
                [51, 13, 11, 5, 1],
                [51, 13, 10, 8, 4],
                [51, 13, 10, 8, 3],
                [51, 13, 10, 8, 2],
                [51, 13, 10, 8, 1],
                [51, 13, 10, 7, 4],
                [51, 13, 10, 7, 3],
                [51, 13, 10, 7, 2],
                [51, 13, 10, 7, 1],
                [51, 13, 10, 6, 4],
                [51, 13, 10, 6, 3],
                [51, 13, 10, 6, 2],
                [51, 13, 10, 6, 1],
                [51, 13, 10, 5, 4],
                [51, 13, 10, 5, 3],
                [51, 13, 10, 5, 2],
                [51, 13, 10, 5, 1],
                [51, 13, 9, 8, 4],
                [51, 13, 9, 8, 3],
                [51, 13, 9, 8, 2],
                [51, 13, 9, 8, 1],
                [51, 13, 9, 7, 4],
                [51, 13, 9, 7, 3],
                [51, 13, 9, 7, 2],
                [51, 13, 9, 7, 1],
                [51, 13, 9, 6, 4],
                [51, 13, 9, 6, 3],
                [51, 13, 9, 6, 2],
                [51, 13, 9, 6, 1],
                [51, 13, 9, 5, 4],
                [51, 13, 9, 5, 3],
                [51, 13, 9, 5, 2],
                [51, 13, 9, 5, 1],
                [50, 16, 12, 8, 4],
                [50, 16, 12, 8, 3],
                [50, 16, 12, 8, 2],
                [50, 16, 12, 8, 1],
                [50, 16, 12, 7, 4],
                [50, 16, 12, 7, 3],
                [50, 16, 12, 7, 2],
                [50, 16, 12, 7, 1],
                [50, 16, 12, 6, 4],
                [50, 16, 12, 6, 3],
                [50, 16, 12, 6, 2],
                [50, 16, 12, 6, 1],
                [50, 16, 12, 5, 4],
                [50, 16, 12, 5, 3],
                [50, 16, 12, 5, 2],
                [50, 16, 12, 5, 1],
                [50, 16, 11, 8, 4],
                [50, 16, 11, 8, 3],
                [50, 16, 11, 8, 2],
                [50, 16, 11, 8, 1],
                [50, 16, 11, 7, 4],
                [50, 16, 11, 7, 3],
                [50, 16, 11, 7, 2],
                [50, 16, 11, 7, 1],
                [50, 16, 11, 6, 4],
                [50, 16, 11, 6, 3],
                [50, 16, 11, 6, 2],
                [50, 16, 11, 6, 1],
                [50, 16, 11, 5, 4],
                [50, 16, 11, 5, 3],
                [50, 16, 11, 5, 2],
                [50, 16, 11, 5, 1],
                [50, 16, 10, 8, 4],
                [50, 16, 10, 8, 3],
                [50, 16, 10, 8, 2],
                [50, 16, 10, 8, 1],
                [50, 16, 10, 7, 4],
                [50, 16, 10, 7, 3],
                [50, 16, 10, 7, 2],
                [50, 16, 10, 7, 1],
                [50, 16, 10, 6, 4],
                [50, 16, 10, 6, 3],
                [50, 16, 10, 6, 2],
                [50, 16, 10, 6, 1],
                [50, 16, 10, 5, 4],
                [50, 16, 10, 5, 3],
                [50, 16, 10, 5, 2],
                [50, 16, 10, 5, 1],
                [50, 16, 9, 8, 4],
                [50, 16, 9, 8, 3],
                [50, 16, 9, 8, 2],
                [50, 16, 9, 8, 1],
                [50, 16, 9, 7, 4],
                [50, 16, 9, 7, 3],
                [50, 16, 9, 7, 2],
                [50, 16, 9, 7, 1],
                [50, 16, 9, 6, 4],
                [50, 16, 9, 6, 3],
                [50, 16, 9, 6, 2],
                [50, 16, 9, 6, 1],
                [50, 16, 9, 5, 4],
                [50, 16, 9, 5, 3],
                [50, 16, 9, 5, 2],
                [50, 16, 9, 5, 1],
                [50, 15, 12, 8, 4],
                [50, 15, 12, 8, 3],
                [50, 15, 12, 8, 2],
                [50, 15, 12, 8, 1],
                [50, 15, 12, 7, 4],
                [50, 15, 12, 7, 3],
                [50, 15, 12, 7, 2],
                [50, 15, 12, 7, 1],
                [50, 15, 12, 6, 4],
                [50, 15, 12, 6, 3],
                [50, 15, 12, 6, 2],
                [50, 15, 12, 6, 1],
                [50, 15, 12, 5, 4],
                [50, 15, 12, 5, 3],
                [50, 15, 12, 5, 2],
                [50, 15, 12, 5, 1],
                [50, 15, 11, 8, 4],
                [50, 15, 11, 8, 3],
                [50, 15, 11, 8, 2],
                [50, 15, 11, 8, 1],
                [50, 15, 11, 7, 4],
                [50, 15, 11, 7, 3],
                [50, 15, 11, 7, 2],
                [50, 15, 11, 7, 1],
                [50, 15, 11, 6, 4],
                [50, 15, 11, 6, 3],
                [50, 15, 11, 6, 2],
                [50, 15, 11, 6, 1],
                [50, 15, 11, 5, 4],
                [50, 15, 11, 5, 3],
                [50, 15, 11, 5, 2],
                [50, 15, 11, 5, 1],
                [50, 15, 10, 8, 4],
                [50, 15, 10, 8, 3],
                [50, 15, 10, 8, 2],
                [50, 15, 10, 8, 1],
                [50, 15, 10, 7, 4],
                [50, 15, 10, 7, 3],
                [50, 15, 10, 7, 2],
                [50, 15, 10, 7, 1],
                [50, 15, 10, 6, 4],
                [50, 15, 10, 6, 3],
                [50, 15, 10, 6, 2],
                [50, 15, 10, 6, 1],
                [50, 15, 10, 5, 4],
                [50, 15, 10, 5, 3],
                [50, 15, 10, 5, 2],
                [50, 15, 10, 5, 1],
                [50, 15, 9, 8, 4],
                [50, 15, 9, 8, 3],
                [50, 15, 9, 8, 2],
                [50, 15, 9, 8, 1],
                [50, 15, 9, 7, 4],
                [50, 15, 9, 7, 3],
                [50, 15, 9, 7, 2],
                [50, 15, 9, 7, 1],
                [50, 15, 9, 6, 4],
                [50, 15, 9, 6, 3],
                [50, 15, 9, 6, 2],
                [50, 15, 9, 6, 1],
                [50, 15, 9, 5, 4],
                [50, 15, 9, 5, 3],
                [50, 15, 9, 5, 2],
                [50, 15, 9, 5, 1],
                [50, 14, 12, 8, 4],
                [50, 14, 12, 8, 3],
                [50, 14, 12, 8, 2],
                [50, 14, 12, 8, 1],
                [50, 14, 12, 7, 4],
                [50, 14, 12, 7, 3],
                [50, 14, 12, 7, 2],
                [50, 14, 12, 7, 1],
                [50, 14, 12, 6, 4],
                [50, 14, 12, 6, 3],
                [50, 14, 12, 6, 2],
                [50, 14, 12, 6, 1],
                [50, 14, 12, 5, 4],
                [50, 14, 12, 5, 3],
                [50, 14, 12, 5, 2],
                [50, 14, 12, 5, 1],
                [50, 14, 11, 8, 4],
                [50, 14, 11, 8, 3],
                [50, 14, 11, 8, 2],
                [50, 14, 11, 8, 1],
                [50, 14, 11, 7, 4],
                [50, 14, 11, 7, 3],
                [50, 14, 11, 7, 2],
                [50, 14, 11, 7, 1],
                [50, 14, 11, 6, 4],
                [50, 14, 11, 6, 3],
                [50, 14, 11, 6, 2],
                [50, 14, 11, 6, 1],
                [50, 14, 11, 5, 4],
                [50, 14, 11, 5, 3],
                [50, 14, 11, 5, 2],
                [50, 14, 11, 5, 1],
                [50, 14, 10, 8, 4],
                [50, 14, 10, 8, 3],
                [50, 14, 10, 8, 2],
                [50, 14, 10, 8, 1],
                [50, 14, 10, 7, 4],
                [50, 14, 10, 7, 3],
                [50, 14, 10, 7, 2],
                [50, 14, 10, 7, 1],
                [50, 14, 10, 6, 4],
                [50, 14, 10, 6, 3],
                [50, 14, 10, 6, 1],
                [50, 14, 10, 5, 4],
                [50, 14, 10, 5, 3],
                [50, 14, 10, 5, 2],
                [50, 14, 10, 5, 1],
                [50, 14, 9, 8, 4],
                [50, 14, 9, 8, 3],
                [50, 14, 9, 8, 2],
                [50, 14, 9, 8, 1],
                [50, 14, 9, 7, 4],
                [50, 14, 9, 7, 3],
                [50, 14, 9, 7, 2],
                [50, 14, 9, 7, 1],
                [50, 14, 9, 6, 4],
                [50, 14, 9, 6, 3],
                [50, 14, 9, 6, 2],
                [50, 14, 9, 6, 1],
                [50, 14, 9, 5, 4],
                [50, 14, 9, 5, 3],
                [50, 14, 9, 5, 2],
                [50, 14, 9, 5, 1],
                [50, 13, 12, 8, 4],
                [50, 13, 12, 8, 3],
                [50, 13, 12, 8, 2],
                [50, 13, 12, 8, 1],
                [50, 13, 12, 7, 4],
                [50, 13, 12, 7, 3],
                [50, 13, 12, 7, 2],
                [50, 13, 12, 7, 1],
                [50, 13, 12, 6, 4],
                [50, 13, 12, 6, 3],
                [50, 13, 12, 6, 2],
                [50, 13, 12, 6, 1],
                [50, 13, 12, 5, 4],
                [50, 13, 12, 5, 3],
                [50, 13, 12, 5, 2],
                [50, 13, 12, 5, 1],
                [50, 13, 11, 8, 4],
                [50, 13, 11, 8, 3],
                [50, 13, 11, 8, 2],
                [50, 13, 11, 8, 1],
                [50, 13, 11, 7, 4],
                [50, 13, 11, 7, 3],
                [50, 13, 11, 7, 2],
                [50, 13, 11, 7, 1],
                [50, 13, 11, 6, 4],
                [50, 13, 11, 6, 3],
                [50, 13, 11, 6, 2],
                [50, 13, 11, 6, 1],
                [50, 13, 11, 5, 4],
                [50, 13, 11, 5, 3],
                [50, 13, 11, 5, 2],
                [50, 13, 11, 5, 1],
                [50, 13, 10, 8, 4],
                [50, 13, 10, 8, 3],
                [50, 13, 10, 8, 2],
                [50, 13, 10, 8, 1],
                [50, 13, 10, 7, 4],
                [50, 13, 10, 7, 3],
                [50, 13, 10, 7, 2],
                [50, 13, 10, 7, 1],
                [50, 13, 10, 6, 4],
                [50, 13, 10, 6, 3],
                [50, 13, 10, 6, 2],
                [50, 13, 10, 6, 1],
                [50, 13, 10, 5, 4],
                [50, 13, 10, 5, 3],
                [50, 13, 10, 5, 2],
                [50, 13, 10, 5, 1],
                [50, 13, 9, 8, 4],
                [50, 13, 9, 8, 3],
                [50, 13, 9, 8, 2],
                [50, 13, 9, 8, 1],
                [50, 13, 9, 7, 4],
                [50, 13, 9, 7, 3],
                [50, 13, 9, 7, 2],
                [50, 13, 9, 7, 1],
                [50, 13, 9, 6, 4],
                [50, 13, 9, 6, 3],
                [50, 13, 9, 6, 2],
                [50, 13, 9, 6, 1],
                [50, 13, 9, 5, 4],
                [50, 13, 9, 5, 3],
                [50, 13, 9, 5, 2],
                [50, 13, 9, 5, 1],
                [49, 16, 12, 8, 4],
                [49, 16, 12, 8, 3],
                [49, 16, 12, 8, 2],
                [49, 16, 12, 8, 1],
                [49, 16, 12, 7, 4],
                [49, 16, 12, 7, 3],
                [49, 16, 12, 7, 2],
                [49, 16, 12, 7, 1],
                [49, 16, 12, 6, 4],
                [49, 16, 12, 6, 3],
                [49, 16, 12, 6, 2],
                [49, 16, 12, 6, 1],
                [49, 16, 12, 5, 4],
                [49, 16, 12, 5, 3],
                [49, 16, 12, 5, 2],
                [49, 16, 12, 5, 1],
                [49, 16, 11, 8, 4],
                [49, 16, 11, 8, 3],
                [49, 16, 11, 8, 2],
                [49, 16, 11, 8, 1],
                [49, 16, 11, 7, 4],
                [49, 16, 11, 7, 3],
                [49, 16, 11, 7, 2],
                [49, 16, 11, 7, 1],
                [49, 16, 11, 6, 4],
                [49, 16, 11, 6, 3],
                [49, 16, 11, 6, 2],
                [49, 16, 11, 6, 1],
                [49, 16, 11, 5, 4],
                [49, 16, 11, 5, 3],
                [49, 16, 11, 5, 2],
                [49, 16, 11, 5, 1],
                [49, 16, 10, 8, 4],
                [49, 16, 10, 8, 3],
                [49, 16, 10, 8, 2],
                [49, 16, 10, 8, 1],
                [49, 16, 10, 7, 4],
                [49, 16, 10, 7, 3],
                [49, 16, 10, 7, 2],
                [49, 16, 10, 7, 1],
                [49, 16, 10, 6, 4],
                [49, 16, 10, 6, 3],
                [49, 16, 10, 6, 2],
                [49, 16, 10, 6, 1],
                [49, 16, 10, 5, 4],
                [49, 16, 10, 5, 3],
                [49, 16, 10, 5, 2],
                [49, 16, 10, 5, 1],
                [49, 16, 9, 8, 4],
                [49, 16, 9, 8, 3],
                [49, 16, 9, 8, 2],
                [49, 16, 9, 8, 1],
                [49, 16, 9, 7, 4],
                [49, 16, 9, 7, 3],
                [49, 16, 9, 7, 2],
                [49, 16, 9, 7, 1],
                [49, 16, 9, 6, 4],
                [49, 16, 9, 6, 3],
                [49, 16, 9, 6, 2],
                [49, 16, 9, 6, 1],
                [49, 16, 9, 5, 4],
                [49, 16, 9, 5, 3],
                [49, 16, 9, 5, 2],
                [49, 16, 9, 5, 1],
                [49, 15, 12, 8, 4],
                [49, 15, 12, 8, 3],
                [49, 15, 12, 8, 2],
                [49, 15, 12, 8, 1],
                [49, 15, 12, 7, 4],
                [49, 15, 12, 7, 3],
                [49, 15, 12, 7, 2],
                [49, 15, 12, 7, 1],
                [49, 15, 12, 6, 4],
                [49, 15, 12, 6, 3],
                [49, 15, 12, 6, 2],
                [49, 15, 12, 6, 1],
                [49, 15, 12, 5, 4],
                [49, 15, 12, 5, 3],
                [49, 15, 12, 5, 2],
                [49, 15, 12, 5, 1],
                [49, 15, 11, 8, 4],
                [49, 15, 11, 8, 3],
                [49, 15, 11, 8, 2],
                [49, 15, 11, 8, 1],
                [49, 15, 11, 7, 4],
                [49, 15, 11, 7, 3],
                [49, 15, 11, 7, 2],
                [49, 15, 11, 7, 1],
                [49, 15, 11, 6, 4],
                [49, 15, 11, 6, 3],
                [49, 15, 11, 6, 2],
                [49, 15, 11, 6, 1],
                [49, 15, 11, 5, 4],
                [49, 15, 11, 5, 3],
                [49, 15, 11, 5, 2],
                [49, 15, 11, 5, 1],
                [49, 15, 10, 8, 4],
                [49, 15, 10, 8, 3],
                [49, 15, 10, 8, 2],
                [49, 15, 10, 8, 1],
                [49, 15, 10, 7, 4],
                [49, 15, 10, 7, 3],
                [49, 15, 10, 7, 2],
                [49, 15, 10, 7, 1],
                [49, 15, 10, 6, 4],
                [49, 15, 10, 6, 3],
                [49, 15, 10, 6, 2],
                [49, 15, 10, 6, 1],
                [49, 15, 10, 5, 4],
                [49, 15, 10, 5, 3],
                [49, 15, 10, 5, 2],
                [49, 15, 10, 5, 1],
                [49, 15, 9, 8, 4],
                [49, 15, 9, 8, 3],
                [49, 15, 9, 8, 2],
                [49, 15, 9, 8, 1],
                [49, 15, 9, 7, 4],
                [49, 15, 9, 7, 3],
                [49, 15, 9, 7, 2],
                [49, 15, 9, 7, 1],
                [49, 15, 9, 6, 4],
                [49, 15, 9, 6, 3],
                [49, 15, 9, 6, 2],
                [49, 15, 9, 6, 1],
                [49, 15, 9, 5, 4],
                [49, 15, 9, 5, 3],
                [49, 15, 9, 5, 2],
                [49, 15, 9, 5, 1],
                [49, 14, 12, 8, 4],
                [49, 14, 12, 8, 3],
                [49, 14, 12, 8, 2],
                [49, 14, 12, 8, 1],
                [49, 14, 12, 7, 4],
                [49, 14, 12, 7, 3],
                [49, 14, 12, 7, 2],
                [49, 14, 12, 7, 1],
                [49, 14, 12, 6, 4],
                [49, 14, 12, 6, 3],
                [49, 14, 12, 6, 2],
                [49, 14, 12, 6, 1],
                [49, 14, 12, 5, 4],
                [49, 14, 12, 5, 3],
                [49, 14, 12, 5, 2],
                [49, 14, 12, 5, 1],
                [49, 14, 11, 8, 4],
                [49, 14, 11, 8, 3],
                [49, 14, 11, 8, 2],
                [49, 14, 11, 8, 1],
                [49, 14, 11, 7, 4],
                [49, 14, 11, 7, 3],
                [49, 14, 11, 7, 2],
                [49, 14, 11, 7, 1],
                [49, 14, 11, 6, 4],
                [49, 14, 11, 6, 3],
                [49, 14, 11, 6, 2],
                [49, 14, 11, 6, 1],
                [49, 14, 11, 5, 4],
                [49, 14, 11, 5, 3],
                [49, 14, 11, 5, 2],
                [49, 14, 11, 5, 1],
                [49, 14, 10, 8, 4],
                [49, 14, 10, 8, 3],
                [49, 14, 10, 8, 2],
                [49, 14, 10, 8, 1],
                [49, 14, 10, 7, 4],
                [49, 14, 10, 7, 3],
                [49, 14, 10, 7, 2],
                [49, 14, 10, 7, 1],
                [49, 14, 10, 6, 4],
                [49, 14, 10, 6, 3],
                [49, 14, 10, 6, 2],
                [49, 14, 10, 6, 1],
                [49, 14, 10, 5, 4],
                [49, 14, 10, 5, 3],
                [49, 14, 10, 5, 2],
                [49, 14, 10, 5, 1],
                [49, 14, 9, 8, 4],
                [49, 14, 9, 8, 3],
                [49, 14, 9, 8, 2],
                [49, 14, 9, 8, 1],
                [49, 14, 9, 7, 4],
                [49, 14, 9, 7, 3],
                [49, 14, 9, 7, 2],
                [49, 14, 9, 7, 1],
                [49, 14, 9, 6, 4],
                [49, 14, 9, 6, 3],
                [49, 14, 9, 6, 2],
                [49, 14, 9, 6, 1],
                [49, 14, 9, 5, 4],
                [49, 14, 9, 5, 3],
                [49, 14, 9, 5, 2],
                [49, 14, 9, 5, 1],
                [49, 13, 12, 8, 4],
                [49, 13, 12, 8, 3],
                [49, 13, 12, 8, 2],
                [49, 13, 12, 8, 1],
                [49, 13, 12, 7, 4],
                [49, 13, 12, 7, 3],
                [49, 13, 12, 7, 2],
                [49, 13, 12, 7, 1],
                [49, 13, 12, 6, 4],
                [49, 13, 12, 6, 3],
                [49, 13, 12, 6, 2],
                [49, 13, 12, 6, 1],
                [49, 13, 12, 5, 4],
                [49, 13, 12, 5, 3],
                [49, 13, 12, 5, 2],
                [49, 13, 12, 5, 1],
                [49, 13, 11, 8, 4],
                [49, 13, 11, 8, 3],
                [49, 13, 11, 8, 2],
                [49, 13, 11, 8, 1],
                [49, 13, 11, 7, 4],
                [49, 13, 11, 7, 3],
                [49, 13, 11, 7, 2],
                [49, 13, 11, 7, 1],
                [49, 13, 11, 6, 4],
                [49, 13, 11, 6, 3],
                [49, 13, 11, 6, 2],
                [49, 13, 11, 6, 1],
                [49, 13, 11, 5, 4],
                [49, 13, 11, 5, 3],
                [49, 13, 11, 5, 2],
                [49, 13, 11, 5, 1],
                [49, 13, 10, 8, 4],
                [49, 13, 10, 8, 3],
                [49, 13, 10, 8, 2],
                [49, 13, 10, 8, 1],
                [49, 13, 10, 7, 4],
                [49, 13, 10, 7, 3],
                [49, 13, 10, 7, 2],
                [49, 13, 10, 7, 1],
                [49, 13, 10, 6, 4],
                [49, 13, 10, 6, 3],
                [49, 13, 10, 6, 2],
                [49, 13, 10, 6, 1],
                [49, 13, 10, 5, 4],
                [49, 13, 10, 5, 3],
                [49, 13, 10, 5, 2],
                [49, 13, 10, 5, 1],
                [49, 13, 9, 8, 4],
                [49, 13, 9, 8, 3],
                [49, 13, 9, 8, 2],
                [49, 13, 9, 8, 1],
                [49, 13, 9, 7, 4],
                [49, 13, 9, 7, 3],
                [49, 13, 9, 7, 2],
                [49, 13, 9, 7, 1],
                [49, 13, 9, 6, 4],
                [49, 13, 9, 6, 3],
                [49, 13, 9, 6, 2],
                [49, 13, 9, 6, 1],
                [49, 13, 9, 5, 4],
                [49, 13, 9, 5, 3],
                [49, 13, 9, 5, 2],
                //------------------------- 特殊牌型 结束 -----------------------
                [48, 44, 40, 36, 31],
                [48, 44, 40, 36, 30],
                [48, 44, 40, 36, 29],
                [48, 44, 40, 35, 32],
                [48, 44, 40, 35, 31],
                [48, 44, 40, 35, 30],
                [48, 44, 40, 35, 29],
                [48, 44, 40, 34, 32],
                [48, 44, 40, 34, 31],
                [48, 44, 40, 34, 30],
                [48, 44, 40, 34, 29],
                [48, 44, 40, 33, 32],
                [48, 44, 40, 33, 31],
                [48, 44, 40, 33, 30],
                [48, 44, 40, 33, 29],
                [48, 44, 39, 36, 32],
                [48, 44, 39, 36, 31],
                [48, 44, 39, 36, 30],
                [48, 44, 39, 36, 29],
                [48, 44, 39, 35, 32],
                [48, 44, 39, 35, 31],
                [48, 44, 39, 35, 30],
                [48, 44, 39, 35, 29],
                [48, 44, 39, 34, 32],
                [48, 44, 39, 34, 31],
                [48, 44, 39, 34, 30],
                [48, 44, 39, 34, 29],
                [48, 44, 39, 33, 32],
                [48, 44, 39, 33, 31],
                [48, 44, 39, 33, 30],
                [48, 44, 39, 33, 29],
                [48, 44, 38, 36, 32],
                [48, 44, 38, 36, 31],
                [48, 44, 38, 36, 30],
                [48, 44, 38, 36, 29],
                [48, 44, 38, 35, 32],
                [48, 44, 38, 35, 31],
                [48, 44, 38, 35, 30],
                [48, 44, 38, 35, 29],
                [48, 44, 38, 34, 32],
                [48, 44, 38, 34, 31],
                [48, 44, 38, 34, 30],
                [48, 44, 38, 34, 29],
                [48, 44, 38, 33, 32],
                [48, 44, 38, 33, 31],
                [48, 44, 38, 33, 30],
                [48, 44, 38, 33, 29],
                [48, 44, 37, 36, 32],
                [48, 44, 37, 36, 31],
                [48, 44, 37, 36, 30],
                [48, 44, 37, 36, 29],
                [48, 44, 37, 35, 32],
                [48, 44, 37, 35, 31],
                [48, 44, 37, 35, 30],
                [48, 44, 37, 35, 29],
                [48, 44, 37, 34, 32],
                [48, 44, 37, 34, 31],
                [48, 44, 37, 34, 30],
                [48, 44, 37, 34, 29],
                [48, 44, 37, 33, 32],
                [48, 44, 37, 33, 31],
                [48, 44, 37, 33, 30],
                [48, 44, 37, 33, 29],
                [48, 43, 40, 36, 32],
                [48, 43, 40, 36, 31],
                [48, 43, 40, 36, 30],
                [48, 43, 40, 36, 29],
                [48, 43, 40, 35, 32],
                [48, 43, 40, 35, 31],
                [48, 43, 40, 35, 30],
                [48, 43, 40, 35, 29],
                [48, 43, 40, 34, 32],
                [48, 43, 40, 34, 31],
                [48, 43, 40, 34, 30],
                [48, 43, 40, 34, 29],
                [48, 43, 40, 33, 32],
                [48, 43, 40, 33, 31],
                [48, 43, 40, 33, 30],
                [48, 43, 40, 33, 29],
                [48, 43, 39, 36, 32],
                [48, 43, 39, 36, 31],
                [48, 43, 39, 36, 30],
                [48, 43, 39, 36, 29],
                [48, 43, 39, 35, 32],
                [48, 43, 39, 35, 31],
                [48, 43, 39, 35, 30],
                [48, 43, 39, 35, 29],
                [48, 43, 39, 34, 32],
                [48, 43, 39, 34, 31],
                [48, 43, 39, 34, 30],
                [48, 43, 39, 34, 29],
                [48, 43, 39, 33, 32],
                [48, 43, 39, 33, 31],
                [48, 43, 39, 33, 30],
                [48, 43, 39, 33, 29],
                [48, 43, 38, 36, 32],
                [48, 43, 38, 36, 31],
                [48, 43, 38, 36, 30],
                [48, 43, 38, 36, 29],
                [48, 43, 38, 35, 32],
                [48, 43, 38, 35, 31],
                [48, 43, 38, 35, 30],
                [48, 43, 38, 35, 29],
                [48, 43, 38, 34, 32],
                [48, 43, 38, 34, 31],
                [48, 43, 38, 34, 30],
                [48, 43, 38, 34, 29],
                [48, 43, 38, 33, 32],
                [48, 43, 38, 33, 31],
                [48, 43, 38, 33, 30],
                [48, 43, 38, 33, 29],
                [48, 43, 37, 36, 32],
                [48, 43, 37, 36, 31],
                [48, 43, 37, 36, 30],
                [48, 43, 37, 36, 29],
                [48, 43, 37, 35, 32],
                [48, 43, 37, 35, 31],
                [48, 43, 37, 35, 30],
                [48, 43, 37, 35, 29],
                [48, 43, 37, 34, 32],
                [48, 43, 37, 34, 31],
                [48, 43, 37, 34, 30],
                [48, 43, 37, 34, 29],
                [48, 43, 37, 33, 32],
                [48, 43, 37, 33, 31],
                [48, 43, 37, 33, 30],
                [48, 43, 37, 33, 29],
                [48, 42, 40, 36, 32],
                [48, 42, 40, 36, 31],
                [48, 42, 40, 36, 30],
                [48, 42, 40, 36, 29],
                [48, 42, 40, 35, 32],
                [48, 42, 40, 35, 31],
                [48, 42, 40, 35, 30],
                [48, 42, 40, 35, 29],
                [48, 42, 40, 34, 32],
                [48, 42, 40, 34, 31],
                [48, 42, 40, 34, 30],
                [48, 42, 40, 34, 29],
                [48, 42, 40, 33, 32],
                [48, 42, 40, 33, 31],
                [48, 42, 40, 33, 30],
                [48, 42, 40, 33, 29],
                [48, 42, 39, 36, 32],
                [48, 42, 39, 36, 31],
                [48, 42, 39, 36, 30],
                [48, 42, 39, 36, 29],
                [48, 42, 39, 35, 32],
                [48, 42, 39, 35, 31],
                [48, 42, 39, 35, 30],
                [48, 42, 39, 35, 29],
                [48, 42, 39, 34, 32],
                [48, 42, 39, 34, 31],
                [48, 42, 39, 34, 30],
                [48, 42, 39, 34, 29],
                [48, 42, 39, 33, 32],
                [48, 42, 39, 33, 31],
                [48, 42, 39, 33, 30],
                [48, 42, 39, 33, 29],
                [48, 42, 38, 36, 32],
                [48, 42, 38, 36, 31],
                [48, 42, 38, 36, 30],
                [48, 42, 38, 36, 29],
                [48, 42, 38, 35, 32],
                [48, 42, 38, 35, 31],
                [48, 42, 38, 35, 30],
                [48, 42, 38, 35, 29],
                [48, 42, 38, 34, 32],
                [48, 42, 38, 34, 31],
                [48, 42, 38, 34, 30],
                [48, 42, 38, 34, 29],
                [48, 42, 38, 33, 32],
                [48, 42, 38, 33, 31],
                [48, 42, 38, 33, 30],
                [48, 42, 38, 33, 29],
                [48, 42, 37, 36, 32],
                [48, 42, 37, 36, 31],
                [48, 42, 37, 36, 30],
                [48, 42, 37, 36, 29],
                [48, 42, 37, 35, 32],
                [48, 42, 37, 35, 31],
                [48, 42, 37, 35, 30],
                [48, 42, 37, 35, 29],
                [48, 42, 37, 34, 32],
                [48, 42, 37, 34, 31],
                [48, 42, 37, 34, 30],
                [48, 42, 37, 34, 29],
                [48, 42, 37, 33, 32],
                [48, 42, 37, 33, 31],
                [48, 42, 37, 33, 30],
                [48, 42, 37, 33, 29],
                [48, 41, 40, 36, 32],
                [48, 41, 40, 36, 31],
                [48, 41, 40, 36, 30],
                [48, 41, 40, 36, 29],
                [48, 41, 40, 35, 32],
                [48, 41, 40, 35, 31],
                [48, 41, 40, 35, 30],
                [48, 41, 40, 35, 29],
                [48, 41, 40, 34, 32],
                [48, 41, 40, 34, 31],
                [48, 41, 40, 34, 30],
                [48, 41, 40, 34, 29],
                [48, 41, 40, 33, 32],
                [48, 41, 40, 33, 31],
                [48, 41, 40, 33, 30],
                [48, 41, 40, 33, 29],
                [48, 41, 39, 36, 32],
                [48, 41, 39, 36, 31],
                [48, 41, 39, 36, 30],
                [48, 41, 39, 36, 29],
                [48, 41, 39, 35, 32],
                [48, 41, 39, 35, 31],
                [48, 41, 39, 35, 30],
                [48, 41, 39, 35, 29],
                [48, 41, 39, 34, 32],
                [48, 41, 39, 34, 31],
                [48, 41, 39, 34, 30],
                [48, 41, 39, 34, 29],
                [48, 41, 39, 33, 32],
                [48, 41, 39, 33, 31],
                [48, 41, 39, 33, 30],
                [48, 41, 39, 33, 29],
                [48, 41, 38, 36, 32],
                [48, 41, 38, 36, 31],
                [48, 41, 38, 36, 30],
                [48, 41, 38, 36, 29],
                [48, 41, 38, 35, 32],
                [48, 41, 38, 35, 31],
                [48, 41, 38, 35, 30],
                [48, 41, 38, 35, 29],
                [48, 41, 38, 34, 32],
                [48, 41, 38, 34, 31],
                [48, 41, 38, 34, 30],
                [48, 41, 38, 34, 29],
                [48, 41, 38, 33, 32],
                [48, 41, 38, 33, 31],
                [48, 41, 38, 33, 30],
                [48, 41, 38, 33, 29],
                [48, 41, 37, 36, 32],
                [48, 41, 37, 36, 31],
                [48, 41, 37, 36, 30],
                [48, 41, 37, 36, 29],
                [48, 41, 37, 35, 32],
                [48, 41, 37, 35, 31],
                [48, 41, 37, 35, 30],
                [48, 41, 37, 35, 29],
                [48, 41, 37, 34, 32],
                [48, 41, 37, 34, 31],
                [48, 41, 37, 34, 30],
                [48, 41, 37, 34, 29],
                [48, 41, 37, 33, 32],
                [48, 41, 37, 33, 31],
                [48, 41, 37, 33, 30],
                [48, 41, 37, 33, 29],
                [47, 44, 40, 36, 32],
                [47, 44, 40, 36, 31],
                [47, 44, 40, 36, 30],
                [47, 44, 40, 36, 29],
                [47, 44, 40, 35, 32],
                [47, 44, 40, 35, 31],
                [47, 44, 40, 35, 30],
                [47, 44, 40, 35, 29],
                [47, 44, 40, 34, 32],
                [47, 44, 40, 34, 31],
                [47, 44, 40, 34, 30],
                [47, 44, 40, 34, 29],
                [47, 44, 40, 33, 32],
                [47, 44, 40, 33, 31],
                [47, 44, 40, 33, 30],
                [47, 44, 40, 33, 29],
                [47, 44, 39, 36, 32],
                [47, 44, 39, 36, 31],
                [47, 44, 39, 36, 30],
                [47, 44, 39, 36, 29],
                [47, 44, 39, 35, 32],
                [47, 44, 39, 35, 31],
                [47, 44, 39, 35, 30],
                [47, 44, 39, 35, 29],
                [47, 44, 39, 34, 32],
                [47, 44, 39, 34, 31],
                [47, 44, 39, 34, 30],
                [47, 44, 39, 34, 29],
                [47, 44, 39, 33, 32],
                [47, 44, 39, 33, 31],
                [47, 44, 39, 33, 30],
                [47, 44, 39, 33, 29],
                [47, 44, 38, 36, 32],
                [47, 44, 38, 36, 31],
                [47, 44, 38, 36, 30],
                [47, 44, 38, 36, 29],
                [47, 44, 38, 35, 32],
                [47, 44, 38, 35, 31],
                [47, 44, 38, 35, 30],
                [47, 44, 38, 35, 29],
                [47, 44, 38, 34, 32],
                [47, 44, 38, 34, 31],
                [47, 44, 38, 34, 30],
                [47, 44, 38, 34, 29],
                [47, 44, 38, 33, 32],
                [47, 44, 38, 33, 31],
                [47, 44, 38, 33, 30],
                [47, 44, 38, 33, 29],
                [47, 44, 37, 36, 32],
                [47, 44, 37, 36, 31],
                [47, 44, 37, 36, 30],
                [47, 44, 37, 36, 29],
                [47, 44, 37, 35, 32],
                [47, 44, 37, 35, 31],
                [47, 44, 37, 35, 30],
                [47, 44, 37, 35, 29],
                [47, 44, 37, 34, 32],
                [47, 44, 37, 34, 31],
                [47, 44, 37, 34, 30],
                [47, 44, 37, 34, 29],
                [47, 44, 37, 33, 32],
                [47, 44, 37, 33, 31],
                [47, 44, 37, 33, 30],
                [47, 44, 37, 33, 29],
                [47, 43, 40, 36, 32],
                [47, 43, 40, 36, 31],
                [47, 43, 40, 36, 30],
                [47, 43, 40, 36, 29],
                [47, 43, 40, 35, 32],
                [47, 43, 40, 35, 31],
                [47, 43, 40, 35, 30],
                [47, 43, 40, 35, 29],
                [47, 43, 40, 34, 32],
                [47, 43, 40, 34, 31],
                [47, 43, 40, 34, 30],
                [47, 43, 40, 34, 29],
                [47, 43, 40, 33, 32],
                [47, 43, 40, 33, 31],
                [47, 43, 40, 33, 30],
                [47, 43, 40, 33, 29],
                [47, 43, 39, 36, 32],
                [47, 43, 39, 36, 31],
                [47, 43, 39, 36, 30],
                [47, 43, 39, 36, 29],
                [47, 43, 39, 35, 32],
                [47, 43, 39, 35, 30],
                [47, 43, 39, 35, 29],
                [47, 43, 39, 34, 32],
                [47, 43, 39, 34, 31],
                [47, 43, 39, 34, 30],
                [47, 43, 39, 34, 29],
                [47, 43, 39, 33, 32],
                [47, 43, 39, 33, 31],
                [47, 43, 39, 33, 30],
                [47, 43, 39, 33, 29],
                [47, 43, 38, 36, 32],
                [47, 43, 38, 36, 31],
                [47, 43, 38, 36, 30],
                [47, 43, 38, 36, 29],
                [47, 43, 38, 35, 32],
                [47, 43, 38, 35, 31],
                [47, 43, 38, 35, 30],
                [47, 43, 38, 35, 29],
                [47, 43, 38, 34, 32],
                [47, 43, 38, 34, 31],
                [47, 43, 38, 34, 30],
                [47, 43, 38, 34, 29],
                [47, 43, 38, 33, 32],
                [47, 43, 38, 33, 31],
                [47, 43, 38, 33, 30],
                [47, 43, 38, 33, 29],
                [47, 43, 37, 36, 32],
                [47, 43, 37, 36, 31],
                [47, 43, 37, 36, 30],
                [47, 43, 37, 36, 29],
                [47, 43, 37, 35, 32],
                [47, 43, 37, 35, 31],
                [47, 43, 37, 35, 30],
                [47, 43, 37, 35, 29],
                [47, 43, 37, 34, 32],
                [47, 43, 37, 34, 31],
                [47, 43, 37, 34, 30],
                [47, 43, 37, 34, 29],
                [47, 43, 37, 33, 32],
                [47, 43, 37, 33, 31],
                [47, 43, 37, 33, 30],
                [47, 43, 37, 33, 29],
                [47, 42, 40, 36, 32],
                [47, 42, 40, 36, 31],
                [47, 42, 40, 36, 30],
                [47, 42, 40, 36, 29],
                [47, 42, 40, 35, 32],
                [47, 42, 40, 35, 31],
                [47, 42, 40, 35, 30],
                [47, 42, 40, 35, 29],
                [47, 42, 40, 34, 32],
                [47, 42, 40, 34, 31],
                [47, 42, 40, 34, 30],
                [47, 42, 40, 34, 29],
                [47, 42, 40, 33, 32],
                [47, 42, 40, 33, 31],
                [47, 42, 40, 33, 30],
                [47, 42, 40, 33, 29],
                [47, 42, 39, 36, 32],
                [47, 42, 39, 36, 31],
                [47, 42, 39, 36, 30],
                [47, 42, 39, 36, 29],
                [47, 42, 39, 35, 32],
                [47, 42, 39, 35, 31],
                [47, 42, 39, 35, 30],
                [47, 42, 39, 35, 29],
                [47, 42, 39, 34, 32],
                [47, 42, 39, 34, 31],
                [47, 42, 39, 34, 30],
                [47, 42, 39, 34, 29],
                [47, 42, 39, 33, 32],
                [47, 42, 39, 33, 31],
                [47, 42, 39, 33, 30],
                [47, 42, 39, 33, 29],
                [47, 42, 38, 36, 32],
                [47, 42, 38, 36, 31],
                [47, 42, 38, 36, 30],
                [47, 42, 38, 36, 29],
                [47, 42, 38, 35, 32],
                [47, 42, 38, 35, 31],
                [47, 42, 38, 35, 30],
                [47, 42, 38, 35, 29],
                [47, 42, 38, 34, 32],
                [47, 42, 38, 34, 31],
                [47, 42, 38, 34, 30],
                [47, 42, 38, 34, 29],
                [47, 42, 38, 33, 32],
                [47, 42, 38, 33, 31],
                [47, 42, 38, 33, 30],
                [47, 42, 38, 33, 29],
                [47, 42, 37, 36, 32],
                [47, 42, 37, 36, 31],
                [47, 42, 37, 36, 30],
                [47, 42, 37, 36, 29],
                [47, 42, 37, 35, 32],
                [47, 42, 37, 35, 31],
                [47, 42, 37, 35, 30],
                [47, 42, 37, 35, 29],
                [47, 42, 37, 34, 32],
                [47, 42, 37, 34, 31],
                [47, 42, 37, 34, 30],
                [47, 42, 37, 34, 29],
                [47, 42, 37, 33, 32],
                [47, 42, 37, 33, 31],
                [47, 42, 37, 33, 30],
                [47, 42, 37, 33, 29],
                [47, 41, 40, 36, 32],
                [47, 41, 40, 36, 31],
                [47, 41, 40, 36, 30],
                [47, 41, 40, 36, 29],
                [47, 41, 40, 35, 32],
                [47, 41, 40, 35, 31],
                [47, 41, 40, 35, 30],
                [47, 41, 40, 35, 29],
                [47, 41, 40, 34, 32],
                [47, 41, 40, 34, 31],
                [47, 41, 40, 34, 30],
                [47, 41, 40, 34, 29],
                [47, 41, 40, 33, 32],
                [47, 41, 40, 33, 31],
                [47, 41, 40, 33, 30],
                [47, 41, 40, 33, 29],
                [47, 41, 39, 36, 32],
                [47, 41, 39, 36, 31],
                [47, 41, 39, 36, 30],
                [47, 41, 39, 36, 29],
                [47, 41, 39, 35, 32],
                [47, 41, 39, 35, 31],
                [47, 41, 39, 35, 30],
                [47, 41, 39, 35, 29],
                [47, 41, 39, 34, 32],
                [47, 41, 39, 34, 31],
                [47, 41, 39, 34, 30],
                [47, 41, 39, 34, 29],
                [47, 41, 39, 33, 32],
                [47, 41, 39, 33, 31],
                [47, 41, 39, 33, 30],
                [47, 41, 39, 33, 29],
                [47, 41, 38, 36, 32],
                [47, 41, 38, 36, 31],
                [47, 41, 38, 36, 30],
                [47, 41, 38, 36, 29],
                [47, 41, 38, 35, 32],
                [47, 41, 38, 35, 31],
                [47, 41, 38, 35, 30],
                [47, 41, 38, 35, 29],
                [47, 41, 38, 34, 32],
                [47, 41, 38, 34, 31],
                [47, 41, 38, 34, 30],
                [47, 41, 38, 34, 29],
                [47, 41, 38, 33, 32],
                [47, 41, 38, 33, 31],
                [47, 41, 38, 33, 30],
                [47, 41, 38, 33, 29],
                [47, 41, 37, 36, 32],
                [47, 41, 37, 36, 31],
                [47, 41, 37, 36, 30],
                [47, 41, 37, 36, 29],
                [47, 41, 37, 35, 32],
                [47, 41, 37, 35, 31],
                [47, 41, 37, 35, 30],
                [47, 41, 37, 35, 29],
                [47, 41, 37, 34, 32],
                [47, 41, 37, 34, 31],
                [47, 41, 37, 34, 30],
                [47, 41, 37, 34, 29],
                [47, 41, 37, 33, 32],
                [47, 41, 37, 33, 31],
                [47, 41, 37, 33, 30],
                [47, 41, 37, 33, 29],
                [46, 44, 40, 36, 32],
                [46, 44, 40, 36, 31],
                [46, 44, 40, 36, 30],
                [46, 44, 40, 36, 29],
                [46, 44, 40, 35, 32],
                [46, 44, 40, 35, 31],
                [46, 44, 40, 35, 30],
                [46, 44, 40, 35, 29],
                [46, 44, 40, 34, 32],
                [46, 44, 40, 34, 31],
                [46, 44, 40, 34, 30],
                [46, 44, 40, 34, 29],
                [46, 44, 40, 33, 32],
                [46, 44, 40, 33, 31],
                [46, 44, 40, 33, 30],
                [46, 44, 40, 33, 29],
                [46, 44, 39, 36, 32],
                [46, 44, 39, 36, 31],
                [46, 44, 39, 36, 30],
                [46, 44, 39, 36, 29],
                [46, 44, 39, 35, 32],
                [46, 44, 39, 35, 31],
                [46, 44, 39, 35, 30],
                [46, 44, 39, 35, 29],
                [46, 44, 39, 34, 32],
                [46, 44, 39, 34, 31],
                [46, 44, 39, 34, 30],
                [46, 44, 39, 34, 29],
                [46, 44, 39, 33, 32],
                [46, 44, 39, 33, 31],
                [46, 44, 39, 33, 30],
                [46, 44, 39, 33, 29],
                [46, 44, 38, 36, 32],
                [46, 44, 38, 36, 31],
                [46, 44, 38, 36, 30],
                [46, 44, 38, 36, 29],
                [46, 44, 38, 35, 32],
                [46, 44, 38, 35, 31],
                [46, 44, 38, 35, 30],
                [46, 44, 38, 35, 29],
                [46, 44, 38, 34, 32],
                [46, 44, 38, 34, 31],
                [46, 44, 38, 34, 30],
                [46, 44, 38, 34, 29],
                [46, 44, 38, 33, 32],
                [46, 44, 38, 33, 31],
                [46, 44, 38, 33, 30],
                [46, 44, 38, 33, 29],
                [46, 44, 37, 36, 32],
                [46, 44, 37, 36, 31],
                [46, 44, 37, 36, 30],
                [46, 44, 37, 36, 29],
                [46, 44, 37, 35, 32],
                [46, 44, 37, 35, 31],
                [46, 44, 37, 35, 30],
                [46, 44, 37, 35, 29],
                [46, 44, 37, 34, 32],
                [46, 44, 37, 34, 31],
                [46, 44, 37, 34, 30],
                [46, 44, 37, 34, 29],
                [46, 44, 37, 33, 32],
                [46, 44, 37, 33, 31],
                [46, 44, 37, 33, 30],
                [46, 44, 37, 33, 29],
                [46, 43, 40, 36, 32],
                [46, 43, 40, 36, 31],
                [46, 43, 40, 36, 30],
                [46, 43, 40, 36, 29],
                [46, 43, 40, 35, 32],
                [46, 43, 40, 35, 31],
                [46, 43, 40, 35, 30],
                [46, 43, 40, 35, 29],
                [46, 43, 40, 34, 32],
                [46, 43, 40, 34, 31],
                [46, 43, 40, 34, 30],
                [46, 43, 40, 34, 29],
                [46, 43, 40, 33, 32],
                [46, 43, 40, 33, 31],
                [46, 43, 40, 33, 30],
                [46, 43, 40, 33, 29],
                [46, 43, 39, 36, 32],
                [46, 43, 39, 36, 31],
                [46, 43, 39, 36, 30],
                [46, 43, 39, 36, 29],
                [46, 43, 39, 35, 32],
                [46, 43, 39, 35, 31],
                [46, 43, 39, 35, 30],
                [46, 43, 39, 35, 29],
                [46, 43, 39, 34, 32],
                [46, 43, 39, 34, 31],
                [46, 43, 39, 34, 30],
                [46, 43, 39, 34, 29],
                [46, 43, 39, 33, 32],
                [46, 43, 39, 33, 31],
                [46, 43, 39, 33, 30],
                [46, 43, 39, 33, 29],
                [46, 43, 38, 36, 32],
                [46, 43, 38, 36, 31],
                [46, 43, 38, 36, 30],
                [46, 43, 38, 36, 29],
                [46, 43, 38, 35, 32],
                [46, 43, 38, 35, 31],
                [46, 43, 38, 35, 30],
                [46, 43, 38, 35, 29],
                [46, 43, 38, 34, 32],
                [46, 43, 38, 34, 31],
                [46, 43, 38, 34, 30],
                [46, 43, 38, 34, 29],
                [46, 43, 38, 33, 32],
                [46, 43, 38, 33, 31],
                [46, 43, 38, 33, 30],
                [46, 43, 38, 33, 29],
                [46, 43, 37, 36, 32],
                [46, 43, 37, 36, 31],
                [46, 43, 37, 36, 30],
                [46, 43, 37, 36, 29],
                [46, 43, 37, 35, 32],
                [46, 43, 37, 35, 31],
                [46, 43, 37, 35, 30],
                [46, 43, 37, 35, 29],
                [46, 43, 37, 34, 32],
                [46, 43, 37, 34, 31],
                [46, 43, 37, 34, 30],
                [46, 43, 37, 34, 29],
                [46, 43, 37, 33, 32],
                [46, 43, 37, 33, 31],
                [46, 43, 37, 33, 30],
                [46, 43, 37, 33, 29],
                [46, 42, 40, 36, 32],
                [46, 42, 40, 36, 31],
                [46, 42, 40, 36, 30],
                [46, 42, 40, 36, 29],
                [46, 42, 40, 35, 32],
                [46, 42, 40, 35, 31],
                [46, 42, 40, 35, 30],
                [46, 42, 40, 35, 29],
                [46, 42, 40, 34, 32],
                [46, 42, 40, 34, 31],
                [46, 42, 40, 34, 30],
                [46, 42, 40, 34, 29],
                [46, 42, 40, 33, 32],
                [46, 42, 40, 33, 31],
                [46, 42, 40, 33, 30],
                [46, 42, 40, 33, 29],
                [46, 42, 39, 36, 32],
                [46, 42, 39, 36, 31],
                [46, 42, 39, 36, 30],
                [46, 42, 39, 36, 29],
                [46, 42, 39, 35, 32],
                [46, 42, 39, 35, 31],
                [46, 42, 39, 35, 30],
                [46, 42, 39, 35, 29],
                [46, 42, 39, 34, 32],
                [46, 42, 39, 34, 31],
                [46, 42, 39, 34, 30],
                [46, 42, 39, 34, 29],
                [46, 42, 39, 33, 32],
                [46, 42, 39, 33, 31],
                [46, 42, 39, 33, 30],
                [46, 42, 39, 33, 29],
                [46, 42, 38, 36, 32],
                [46, 42, 38, 36, 31],
                [46, 42, 38, 36, 30],
                [46, 42, 38, 36, 29],
                [46, 42, 38, 35, 32],
                [46, 42, 38, 35, 31],
                [46, 42, 38, 35, 30],
                [46, 42, 38, 35, 29],
                [46, 42, 38, 34, 32],
                [46, 42, 38, 34, 31],
                [46, 42, 38, 34, 29],
                [46, 42, 38, 33, 32],
                [46, 42, 38, 33, 31],
                [46, 42, 38, 33, 30],
                [46, 42, 38, 33, 29],
                [46, 42, 37, 36, 32],
                [46, 42, 37, 36, 31],
                [46, 42, 37, 36, 30],
                [46, 42, 37, 36, 29],
                [46, 42, 37, 35, 32],
                [46, 42, 37, 35, 31],
                [46, 42, 37, 35, 30],
                [46, 42, 37, 35, 29],
                [46, 42, 37, 34, 32],
                [46, 42, 37, 34, 31],
                [46, 42, 37, 34, 30],
                [46, 42, 37, 34, 29],
                [46, 42, 37, 33, 32],
                [46, 42, 37, 33, 31],
                [46, 42, 37, 33, 30],
                [46, 42, 37, 33, 29],
                [46, 41, 40, 36, 32],
                [46, 41, 40, 36, 31],
                [46, 41, 40, 36, 30],
                [46, 41, 40, 36, 29],
                [46, 41, 40, 35, 32],
                [46, 41, 40, 35, 31],
                [46, 41, 40, 35, 30],
                [46, 41, 40, 35, 29],
                [46, 41, 40, 34, 32],
                [46, 41, 40, 34, 31],
                [46, 41, 40, 34, 30],
                [46, 41, 40, 34, 29],
                [46, 41, 40, 33, 32],
                [46, 41, 40, 33, 31],
                [46, 41, 40, 33, 30],
                [46, 41, 40, 33, 29],
                [46, 41, 39, 36, 32],
                [46, 41, 39, 36, 31],
                [46, 41, 39, 36, 30],
                [46, 41, 39, 36, 29],
                [46, 41, 39, 35, 32],
                [46, 41, 39, 35, 31],
                [46, 41, 39, 35, 30],
                [46, 41, 39, 35, 29],
                [46, 41, 39, 34, 32],
                [46, 41, 39, 34, 31],
                [46, 41, 39, 34, 30],
                [46, 41, 39, 34, 29],
                [46, 41, 39, 33, 32],
                [46, 41, 39, 33, 31],
                [46, 41, 39, 33, 30],
                [46, 41, 39, 33, 29],
                [46, 41, 38, 36, 32],
                [46, 41, 38, 36, 31],
                [46, 41, 38, 36, 30],
                [46, 41, 38, 36, 29],
                [46, 41, 38, 35, 32],
                [46, 41, 38, 35, 31],
                [46, 41, 38, 35, 30],
                [46, 41, 38, 35, 29],
                [46, 41, 38, 34, 32],
                [46, 41, 38, 34, 31],
                [46, 41, 38, 34, 30],
                [46, 41, 38, 34, 29],
                [46, 41, 38, 33, 32],
                [46, 41, 38, 33, 31],
                [46, 41, 38, 33, 30],
                [46, 41, 38, 33, 29],
                [46, 41, 37, 36, 32],
                [46, 41, 37, 36, 31],
                [46, 41, 37, 36, 30],
                [46, 41, 37, 36, 29],
                [46, 41, 37, 35, 32],
                [46, 41, 37, 35, 31],
                [46, 41, 37, 35, 30],
                [46, 41, 37, 35, 29],
                [46, 41, 37, 34, 32],
                [46, 41, 37, 34, 31],
                [46, 41, 37, 34, 30],
                [46, 41, 37, 34, 29],
                [46, 41, 37, 33, 32],
                [46, 41, 37, 33, 31],
                [46, 41, 37, 33, 30],
                [46, 41, 37, 33, 29],
                [45, 44, 40, 36, 32],
                [45, 44, 40, 36, 31],
                [45, 44, 40, 36, 30],
                [45, 44, 40, 36, 29],
                [45, 44, 40, 35, 32],
                [45, 44, 40, 35, 31],
                [45, 44, 40, 35, 30],
                [45, 44, 40, 35, 29],
                [45, 44, 40, 34, 32],
                [45, 44, 40, 34, 31],
                [45, 44, 40, 34, 30],
                [45, 44, 40, 34, 29],
                [45, 44, 40, 33, 32],
                [45, 44, 40, 33, 31],
                [45, 44, 40, 33, 30],
                [45, 44, 40, 33, 29],
                [45, 44, 39, 36, 32],
                [45, 44, 39, 36, 31],
                [45, 44, 39, 36, 30],
                [45, 44, 39, 36, 29],
                [45, 44, 39, 35, 32],
                [45, 44, 39, 35, 31],
                [45, 44, 39, 35, 30],
                [45, 44, 39, 35, 29],
                [45, 44, 39, 34, 32],
                [45, 44, 39, 34, 31],
                [45, 44, 39, 34, 30],
                [45, 44, 39, 34, 29],
                [45, 44, 39, 33, 32],
                [45, 44, 39, 33, 31],
                [45, 44, 39, 33, 30],
                [45, 44, 39, 33, 29],
                [45, 44, 38, 36, 32],
                [45, 44, 38, 36, 31],
                [45, 44, 38, 36, 30],
                [45, 44, 38, 36, 29],
                [45, 44, 38, 35, 32],
                [45, 44, 38, 35, 31],
                [45, 44, 38, 35, 30],
                [45, 44, 38, 35, 29],
                [45, 44, 38, 34, 32],
                [45, 44, 38, 34, 31],
                [45, 44, 38, 34, 30],
                [45, 44, 38, 34, 29],
                [45, 44, 38, 33, 32],
                [45, 44, 38, 33, 31],
                [45, 44, 38, 33, 30],
                [45, 44, 38, 33, 29],
                [45, 44, 37, 36, 32],
                [45, 44, 37, 36, 31],
                [45, 44, 37, 36, 30],
                [45, 44, 37, 36, 29],
                [45, 44, 37, 35, 32],
                [45, 44, 37, 35, 31],
                [45, 44, 37, 35, 30],
                [45, 44, 37, 35, 29],
                [45, 44, 37, 34, 32],
                [45, 44, 37, 34, 31],
                [45, 44, 37, 34, 30],
                [45, 44, 37, 34, 29],
                [45, 44, 37, 33, 32],
                [45, 44, 37, 33, 31],
                [45, 44, 37, 33, 30],
                [45, 44, 37, 33, 29],
                [45, 43, 40, 36, 32],
                [45, 43, 40, 36, 31],
                [45, 43, 40, 36, 30],
                [45, 43, 40, 36, 29],
                [45, 43, 40, 35, 32],
                [45, 43, 40, 35, 31],
                [45, 43, 40, 35, 30],
                [45, 43, 40, 35, 29],
                [45, 43, 40, 34, 32],
                [45, 43, 40, 34, 31],
                [45, 43, 40, 34, 30],
                [45, 43, 40, 34, 29],
                [45, 43, 40, 33, 32],
                [45, 43, 40, 33, 31],
                [45, 43, 40, 33, 30],
                [45, 43, 40, 33, 29],
                [45, 43, 39, 36, 32],
                [45, 43, 39, 36, 31],
                [45, 43, 39, 36, 30],
                [45, 43, 39, 36, 29],
                [45, 43, 39, 35, 32],
                [45, 43, 39, 35, 31],
                [45, 43, 39, 35, 30],
                [45, 43, 39, 35, 29],
                [45, 43, 39, 34, 32],
                [45, 43, 39, 34, 31],
                [45, 43, 39, 34, 30],
                [45, 43, 39, 34, 29],
                [45, 43, 39, 33, 32],
                [45, 43, 39, 33, 31],
                [45, 43, 39, 33, 30],
                [45, 43, 39, 33, 29],
                [45, 43, 38, 36, 32],
                [45, 43, 38, 36, 31],
                [45, 43, 38, 36, 30],
                [45, 43, 38, 36, 29],
                [45, 43, 38, 35, 32],
                [45, 43, 38, 35, 31],
                [45, 43, 38, 35, 30],
                [45, 43, 38, 35, 29],
                [45, 43, 38, 34, 32],
                [45, 43, 38, 34, 31],
                [45, 43, 38, 34, 30],
                [45, 43, 38, 34, 29],
                [45, 43, 38, 33, 32],
                [45, 43, 38, 33, 31],
                [45, 43, 38, 33, 30],
                [45, 43, 38, 33, 29],
                [45, 43, 37, 36, 32],
                [45, 43, 37, 36, 31],
                [45, 43, 37, 36, 30],
                [45, 43, 37, 36, 29],
                [45, 43, 37, 35, 32],
                [45, 43, 37, 35, 31],
                [45, 43, 37, 35, 30],
                [45, 43, 37, 35, 29],
                [45, 43, 37, 34, 32],
                [45, 43, 37, 34, 31],
                [45, 43, 37, 34, 30],
                [45, 43, 37, 34, 29],
                [45, 43, 37, 33, 32],
                [45, 43, 37, 33, 31],
                [45, 43, 37, 33, 30],
                [45, 43, 37, 33, 29],
                [45, 42, 40, 36, 32],
                [45, 42, 40, 36, 31],
                [45, 42, 40, 36, 30],
                [45, 42, 40, 36, 29],
                [45, 42, 40, 35, 32],
                [45, 42, 40, 35, 31],
                [45, 42, 40, 35, 30],
                [45, 42, 40, 35, 29],
                [45, 42, 40, 34, 32],
                [45, 42, 40, 34, 31],
                [45, 42, 40, 34, 30],
                [45, 42, 40, 34, 29],
                [45, 42, 40, 33, 32],
                [45, 42, 40, 33, 31],
                [45, 42, 40, 33, 30],
                [45, 42, 40, 33, 29],
                [45, 42, 39, 36, 32],
                [45, 42, 39, 36, 31],
                [45, 42, 39, 36, 30],
                [45, 42, 39, 36, 29],
                [45, 42, 39, 35, 32],
                [45, 42, 39, 35, 31],
                [45, 42, 39, 35, 30],
                [45, 42, 39, 35, 29],
                [45, 42, 39, 34, 32],
                [45, 42, 39, 34, 31],
                [45, 42, 39, 34, 30],
                [45, 42, 39, 34, 29],
                [45, 42, 39, 33, 32],
                [45, 42, 39, 33, 31],
                [45, 42, 39, 33, 30],
                [45, 42, 39, 33, 29],
                [45, 42, 38, 36, 32],
                [45, 42, 38, 36, 31],
                [45, 42, 38, 36, 30],
                [45, 42, 38, 36, 29],
                [45, 42, 38, 35, 32],
                [45, 42, 38, 35, 31],
                [45, 42, 38, 35, 30],
                [45, 42, 38, 35, 29],
                [45, 42, 38, 34, 32],
                [45, 42, 38, 34, 31],
                [45, 42, 38, 34, 30],
                [45, 42, 38, 34, 29],
                [45, 42, 38, 33, 32],
                [45, 42, 38, 33, 31],
                [45, 42, 38, 33, 30],
                [45, 42, 38, 33, 29],
                [45, 42, 37, 36, 32],
                [45, 42, 37, 36, 31],
                [45, 42, 37, 36, 30],
                [45, 42, 37, 36, 29],
                [45, 42, 37, 35, 32],
                [45, 42, 37, 35, 31],
                [45, 42, 37, 35, 30],
                [45, 42, 37, 35, 29],
                [45, 42, 37, 34, 32],
                [45, 42, 37, 34, 31],
                [45, 42, 37, 34, 30],
                [45, 42, 37, 34, 29],
                [45, 42, 37, 33, 32],
                [45, 42, 37, 33, 31],
                [45, 42, 37, 33, 30],
                [45, 42, 37, 33, 29],
                [45, 41, 40, 36, 32],
                [45, 41, 40, 36, 31],
                [45, 41, 40, 36, 30],
                [45, 41, 40, 36, 29],
                [45, 41, 40, 35, 32],
                [45, 41, 40, 35, 31],
                [45, 41, 40, 35, 30],
                [45, 41, 40, 35, 29],
                [45, 41, 40, 34, 32],
                [45, 41, 40, 34, 31],
                [45, 41, 40, 34, 30],
                [45, 41, 40, 34, 29],
                [45, 41, 40, 33, 32],
                [45, 41, 40, 33, 31],
                [45, 41, 40, 33, 30],
                [45, 41, 40, 33, 29],
                [45, 41, 39, 36, 32],
                [45, 41, 39, 36, 31],
                [45, 41, 39, 36, 30],
                [45, 41, 39, 36, 29],
                [45, 41, 39, 35, 32],
                [45, 41, 39, 35, 31],
                [45, 41, 39, 35, 30],
                [45, 41, 39, 35, 29],
                [45, 41, 39, 34, 32],
                [45, 41, 39, 34, 31],
                [45, 41, 39, 34, 30],
                [45, 41, 39, 34, 29],
                [45, 41, 39, 33, 32],
                [45, 41, 39, 33, 31],
                [45, 41, 39, 33, 30],
                [45, 41, 39, 33, 29],
                [45, 41, 38, 36, 32],
                [45, 41, 38, 36, 31],
                [45, 41, 38, 36, 30],
                [45, 41, 38, 36, 29],
                [45, 41, 38, 35, 32],
                [45, 41, 38, 35, 31],
                [45, 41, 38, 35, 30],
                [45, 41, 38, 35, 29],
                [45, 41, 38, 34, 32],
                [45, 41, 38, 34, 31],
                [45, 41, 38, 34, 30],
                [45, 41, 38, 34, 29],
                [45, 41, 38, 33, 32],
                [45, 41, 38, 33, 31],
                [45, 41, 38, 33, 30],
                [45, 41, 38, 33, 29],
                [45, 41, 37, 36, 32],
                [45, 41, 37, 36, 31],
                [45, 41, 37, 36, 30],
                [45, 41, 37, 36, 29],
                [45, 41, 37, 35, 32],
                [45, 41, 37, 35, 31],
                [45, 41, 37, 35, 30],
                [45, 41, 37, 35, 29],
                [45, 41, 37, 34, 32],
                [45, 41, 37, 34, 31],
                [45, 41, 37, 34, 30],
                [45, 41, 37, 34, 29],
                [45, 41, 37, 33, 32],
                [45, 41, 37, 33, 31],
                [45, 41, 37, 33, 30],
                [44, 40, 36, 32, 27],
                [44, 40, 36, 32, 26],
                [44, 40, 36, 32, 25],
                [44, 40, 36, 31, 28],
                [44, 40, 36, 31, 27],
                [44, 40, 36, 31, 26],
                [44, 40, 36, 31, 25],
                [44, 40, 36, 30, 28],
                [44, 40, 36, 30, 27],
                [44, 40, 36, 30, 26],
                [44, 40, 36, 30, 25],
                [44, 40, 36, 29, 28],
                [44, 40, 36, 29, 27],
                [44, 40, 36, 29, 26],
                [44, 40, 36, 29, 25],
                [44, 40, 35, 32, 28],
                [44, 40, 35, 32, 27],
                [44, 40, 35, 32, 26],
                [44, 40, 35, 32, 25],
                [44, 40, 35, 31, 28],
                [44, 40, 35, 31, 27],
                [44, 40, 35, 31, 26],
                [44, 40, 35, 31, 25],
                [44, 40, 35, 30, 28],
                [44, 40, 35, 30, 27],
                [44, 40, 35, 30, 26],
                [44, 40, 35, 30, 25],
                [44, 40, 35, 29, 28],
                [44, 40, 35, 29, 27],
                [44, 40, 35, 29, 26],
                [44, 40, 35, 29, 25],
                [44, 40, 34, 32, 28],
                [44, 40, 34, 32, 27],
                [44, 40, 34, 32, 26],
                [44, 40, 34, 32, 25],
                [44, 40, 34, 31, 28],
                [44, 40, 34, 31, 27],
                [44, 40, 34, 31, 26],
                [44, 40, 34, 31, 25],
                [44, 40, 34, 30, 28],
                [44, 40, 34, 30, 27],
                [44, 40, 34, 30, 26],
                [44, 40, 34, 30, 25],
                [44, 40, 34, 29, 28],
                [44, 40, 34, 29, 27],
                [44, 40, 34, 29, 26],
                [44, 40, 34, 29, 25],
                [44, 40, 33, 32, 28],
                [44, 40, 33, 32, 27],
                [44, 40, 33, 32, 26],
                [44, 40, 33, 32, 25],
                [44, 40, 33, 31, 28],
                [44, 40, 33, 31, 27],
                [44, 40, 33, 31, 26],
                [44, 40, 33, 31, 25],
                [44, 40, 33, 30, 28],
                [44, 40, 33, 30, 27],
                [44, 40, 33, 30, 26],
                [44, 40, 33, 30, 25],
                [44, 40, 33, 29, 28],
                [44, 40, 33, 29, 27],
                [44, 40, 33, 29, 26],
                [44, 40, 33, 29, 25],
                [44, 39, 36, 32, 28],
                [44, 39, 36, 32, 27],
                [44, 39, 36, 32, 26],
                [44, 39, 36, 32, 25],
                [44, 39, 36, 31, 28],
                [44, 39, 36, 31, 27],
                [44, 39, 36, 31, 26],
                [44, 39, 36, 31, 25],
                [44, 39, 36, 30, 28],
                [44, 39, 36, 30, 27],
                [44, 39, 36, 30, 26],
                [44, 39, 36, 30, 25],
                [44, 39, 36, 29, 28],
                [44, 39, 36, 29, 27],
                [44, 39, 36, 29, 26],
                [44, 39, 36, 29, 25],
                [44, 39, 35, 32, 28],
                [44, 39, 35, 32, 27],
                [44, 39, 35, 32, 26],
                [44, 39, 35, 32, 25],
                [44, 39, 35, 31, 28],
                [44, 39, 35, 31, 27],
                [44, 39, 35, 31, 26],
                [44, 39, 35, 31, 25],
                [44, 39, 35, 30, 28],
                [44, 39, 35, 30, 27],
                [44, 39, 35, 30, 26],
                [44, 39, 35, 30, 25],
                [44, 39, 35, 29, 28],
                [44, 39, 35, 29, 27],
                [44, 39, 35, 29, 26],
                [44, 39, 35, 29, 25],
                [44, 39, 34, 32, 28],
                [44, 39, 34, 32, 27],
                [44, 39, 34, 32, 26],
                [44, 39, 34, 32, 25],
                [44, 39, 34, 31, 28],
                [44, 39, 34, 31, 27],
                [44, 39, 34, 31, 26],
                [44, 39, 34, 31, 25],
                [44, 39, 34, 30, 28],
                [44, 39, 34, 30, 27],
                [44, 39, 34, 30, 26],
                [44, 39, 34, 30, 25],
                [44, 39, 34, 29, 28],
                [44, 39, 34, 29, 27],
                [44, 39, 34, 29, 26],
                [44, 39, 34, 29, 25],
                [44, 39, 33, 32, 28],
                [44, 39, 33, 32, 27],
                [44, 39, 33, 32, 26],
                [44, 39, 33, 32, 25],
                [44, 39, 33, 31, 28],
                [44, 39, 33, 31, 27],
                [44, 39, 33, 31, 26],
                [44, 39, 33, 31, 25],
                [44, 39, 33, 30, 28],
                [44, 39, 33, 30, 27],
                [44, 39, 33, 30, 26],
                [44, 39, 33, 30, 25],
                [44, 39, 33, 29, 28],
                [44, 39, 33, 29, 27],
                [44, 39, 33, 29, 26],
                [44, 39, 33, 29, 25],
                [44, 38, 36, 32, 28],
                [44, 38, 36, 32, 27],
                [44, 38, 36, 32, 26],
                [44, 38, 36, 32, 25],
                [44, 38, 36, 31, 28],
                [44, 38, 36, 31, 27],
                [44, 38, 36, 31, 26],
                [44, 38, 36, 31, 25],
                [44, 38, 36, 30, 28],
                [44, 38, 36, 30, 27],
                [44, 38, 36, 30, 26],
                [44, 38, 36, 30, 25],
                [44, 38, 36, 29, 28],
                [44, 38, 36, 29, 27],
                [44, 38, 36, 29, 26],
                [44, 38, 36, 29, 25],
                [44, 38, 35, 32, 28],
                [44, 38, 35, 32, 27],
                [44, 38, 35, 32, 26],
                [44, 38, 35, 32, 25],
                [44, 38, 35, 31, 28],
                [44, 38, 35, 31, 27],
                [44, 38, 35, 31, 26],
                [44, 38, 35, 31, 25],
                [44, 38, 35, 30, 28],
                [44, 38, 35, 30, 27],
                [44, 38, 35, 30, 26],
                [44, 38, 35, 30, 25],
                [44, 38, 35, 29, 28],
                [44, 38, 35, 29, 27],
                [44, 38, 35, 29, 26],
                [44, 38, 35, 29, 25],
                [44, 38, 34, 32, 28],
                [44, 38, 34, 32, 27],
                [44, 38, 34, 32, 26],
                [44, 38, 34, 32, 25],
                [44, 38, 34, 31, 28],
                [44, 38, 34, 31, 27],
                [44, 38, 34, 31, 26],
                [44, 38, 34, 31, 25],
                [44, 38, 34, 30, 28],
                [44, 38, 34, 30, 27],
                [44, 38, 34, 30, 26],
                [44, 38, 34, 30, 25],
                [44, 38, 34, 29, 28],
                [44, 38, 34, 29, 27],
                [44, 38, 34, 29, 26],
                [44, 38, 34, 29, 25],
                [44, 38, 33, 32, 28],
                [44, 38, 33, 32, 27],
                [44, 38, 33, 32, 26],
                [44, 38, 33, 32, 25],
                [44, 38, 33, 31, 28],
                [44, 38, 33, 31, 27],
                [44, 38, 33, 31, 26],
                [44, 38, 33, 31, 25],
                [44, 38, 33, 30, 28],
                [44, 38, 33, 30, 27],
                [44, 38, 33, 30, 26],
                [44, 38, 33, 30, 25],
                [44, 38, 33, 29, 28],
                [44, 38, 33, 29, 27],
                [44, 38, 33, 29, 26],
                [44, 38, 33, 29, 25],
                [44, 37, 36, 32, 28],
                [44, 37, 36, 32, 27],
                [44, 37, 36, 32, 26],
                [44, 37, 36, 32, 25],
                [44, 37, 36, 31, 28],
                [44, 37, 36, 31, 27],
                [44, 37, 36, 31, 26],
                [44, 37, 36, 31, 25],
                [44, 37, 36, 30, 28],
                [44, 37, 36, 30, 27],
                [44, 37, 36, 30, 26],
                [44, 37, 36, 30, 25],
                [44, 37, 36, 29, 28],
                [44, 37, 36, 29, 27],
                [44, 37, 36, 29, 26],
                [44, 37, 36, 29, 25],
                [44, 37, 35, 32, 28],
                [44, 37, 35, 32, 27],
                [44, 37, 35, 32, 26],
                [44, 37, 35, 32, 25],
                [44, 37, 35, 31, 28],
                [44, 37, 35, 31, 27],
                [44, 37, 35, 31, 26],
                [44, 37, 35, 31, 25],
                [44, 37, 35, 30, 28],
                [44, 37, 35, 30, 27],
                [44, 37, 35, 30, 26],
                [44, 37, 35, 30, 25],
                [44, 37, 35, 29, 28],
                [44, 37, 35, 29, 27],
                [44, 37, 35, 29, 26],
                [44, 37, 35, 29, 25],
                [44, 37, 34, 32, 28],
                [44, 37, 34, 32, 27],
                [44, 37, 34, 32, 26],
                [44, 37, 34, 32, 25],
                [44, 37, 34, 31, 28],
                [44, 37, 34, 31, 27],
                [44, 37, 34, 31, 26],
                [44, 37, 34, 31, 25],
                [44, 37, 34, 30, 28],
                [44, 37, 34, 30, 27],
                [44, 37, 34, 30, 26],
                [44, 37, 34, 30, 25],
                [44, 37, 34, 29, 28],
                [44, 37, 34, 29, 27],
                [44, 37, 34, 29, 26],
                [44, 37, 34, 29, 25],
                [44, 37, 33, 32, 28],
                [44, 37, 33, 32, 27],
                [44, 37, 33, 32, 26],
                [44, 37, 33, 32, 25],
                [44, 37, 33, 31, 28],
                [44, 37, 33, 31, 27],
                [44, 37, 33, 31, 26],
                [44, 37, 33, 31, 25],
                [44, 37, 33, 30, 28],
                [44, 37, 33, 30, 27],
                [44, 37, 33, 30, 26],
                [44, 37, 33, 30, 25],
                [44, 37, 33, 29, 28],
                [44, 37, 33, 29, 27],
                [44, 37, 33, 29, 26],
                [44, 37, 33, 29, 25],
                [43, 40, 36, 32, 28],
                [43, 40, 36, 32, 27],
                [43, 40, 36, 32, 26],
                [43, 40, 36, 32, 25],
                [43, 40, 36, 31, 28],
                [43, 40, 36, 31, 27],
                [43, 40, 36, 31, 26],
                [43, 40, 36, 31, 25],
                [43, 40, 36, 30, 28],
                [43, 40, 36, 30, 27],
                [43, 40, 36, 30, 26],
                [43, 40, 36, 30, 25],
                [43, 40, 36, 29, 28],
                [43, 40, 36, 29, 27],
                [43, 40, 36, 29, 26],
                [43, 40, 36, 29, 25],
                [43, 40, 35, 32, 28],
                [43, 40, 35, 32, 27],
                [43, 40, 35, 32, 26],
                [43, 40, 35, 32, 25],
                [43, 40, 35, 31, 28],
                [43, 40, 35, 31, 27],
                [43, 40, 35, 31, 26],
                [43, 40, 35, 31, 25],
                [43, 40, 35, 30, 28],
                [43, 40, 35, 30, 27],
                [43, 40, 35, 30, 26],
                [43, 40, 35, 30, 25],
                [43, 40, 35, 29, 28],
                [43, 40, 35, 29, 27],
                [43, 40, 35, 29, 26],
                [43, 40, 35, 29, 25],
                [43, 40, 34, 32, 28],
                [43, 40, 34, 32, 27],
                [43, 40, 34, 32, 26],
                [43, 40, 34, 32, 25],
                [43, 40, 34, 31, 28],
                [43, 40, 34, 31, 27],
                [43, 40, 34, 31, 26],
                [43, 40, 34, 31, 25],
                [43, 40, 34, 30, 28],
                [43, 40, 34, 30, 27],
                [43, 40, 34, 30, 26],
                [43, 40, 34, 30, 25],
                [43, 40, 34, 29, 28],
                [43, 40, 34, 29, 27],
                [43, 40, 34, 29, 26],
                [43, 40, 34, 29, 25],
                [43, 40, 33, 32, 28],
                [43, 40, 33, 32, 27],
                [43, 40, 33, 32, 26],
                [43, 40, 33, 32, 25],
                [43, 40, 33, 31, 28],
                [43, 40, 33, 31, 27],
                [43, 40, 33, 31, 26],
                [43, 40, 33, 31, 25],
                [43, 40, 33, 30, 28],
                [43, 40, 33, 30, 27],
                [43, 40, 33, 30, 26],
                [43, 40, 33, 30, 25],
                [43, 40, 33, 29, 28],
                [43, 40, 33, 29, 27],
                [43, 40, 33, 29, 26],
                [43, 40, 33, 29, 25],
                [43, 39, 36, 32, 28],
                [43, 39, 36, 32, 27],
                [43, 39, 36, 32, 26],
                [43, 39, 36, 32, 25],
                [43, 39, 36, 31, 28],
                [43, 39, 36, 31, 27],
                [43, 39, 36, 31, 26],
                [43, 39, 36, 31, 25],
                [43, 39, 36, 30, 28],
                [43, 39, 36, 30, 27],
                [43, 39, 36, 30, 26],
                [43, 39, 36, 30, 25],
                [43, 39, 36, 29, 28],
                [43, 39, 36, 29, 27],
                [43, 39, 36, 29, 26],
                [43, 39, 36, 29, 25],
                [43, 39, 35, 32, 28],
                [43, 39, 35, 32, 27],
                [43, 39, 35, 32, 26],
                [43, 39, 35, 32, 25],
                [43, 39, 35, 31, 28],
                [43, 39, 35, 31, 26],
                [43, 39, 35, 31, 25],
                [43, 39, 35, 30, 28],
                [43, 39, 35, 30, 27],
                [43, 39, 35, 30, 26],
                [43, 39, 35, 30, 25],
                [43, 39, 35, 29, 28],
                [43, 39, 35, 29, 27],
                [43, 39, 35, 29, 26],
                [43, 39, 35, 29, 25],
                [43, 39, 34, 32, 28],
                [43, 39, 34, 32, 27],
                [43, 39, 34, 32, 26],
                [43, 39, 34, 32, 25],
                [43, 39, 34, 31, 28],
                [43, 39, 34, 31, 27],
                [43, 39, 34, 31, 26],
                [43, 39, 34, 31, 25],
                [43, 39, 34, 30, 28],
                [43, 39, 34, 30, 27],
                [43, 39, 34, 30, 26],
                [43, 39, 34, 30, 25],
                [43, 39, 34, 29, 28],
                [43, 39, 34, 29, 27],
                [43, 39, 34, 29, 26],
                [43, 39, 34, 29, 25],
                [43, 39, 33, 32, 28],
                [43, 39, 33, 32, 27],
                [43, 39, 33, 32, 26],
                [43, 39, 33, 32, 25],
                [43, 39, 33, 31, 28],
                [43, 39, 33, 31, 27],
                [43, 39, 33, 31, 26],
                [43, 39, 33, 31, 25],
                [43, 39, 33, 30, 28],
                [43, 39, 33, 30, 27],
                [43, 39, 33, 30, 26],
                [43, 39, 33, 30, 25],
                [43, 39, 33, 29, 28],
                [43, 39, 33, 29, 27],
                [43, 39, 33, 29, 26],
                [43, 39, 33, 29, 25],
                [43, 38, 36, 32, 28],
                [43, 38, 36, 32, 27],
                [43, 38, 36, 32, 26],
                [43, 38, 36, 32, 25],
                [43, 38, 36, 31, 28],
                [43, 38, 36, 31, 27],
                [43, 38, 36, 31, 26],
                [43, 38, 36, 31, 25],
                [43, 38, 36, 30, 28],
                [43, 38, 36, 30, 27],
                [43, 38, 36, 30, 26],
                [43, 38, 36, 30, 25],
                [43, 38, 36, 29, 28],
                [43, 38, 36, 29, 27],
                [43, 38, 36, 29, 26],
                [43, 38, 36, 29, 25],
                [43, 38, 35, 32, 28],
                [43, 38, 35, 32, 27],
                [43, 38, 35, 32, 26],
                [43, 38, 35, 32, 25],
                [43, 38, 35, 31, 28],
                [43, 38, 35, 31, 27],
                [43, 38, 35, 31, 26],
                [43, 38, 35, 31, 25],
                [43, 38, 35, 30, 28],
                [43, 38, 35, 30, 27],
                [43, 38, 35, 30, 26],
                [43, 38, 35, 30, 25],
                [43, 38, 35, 29, 28],
                [43, 38, 35, 29, 27],
                [43, 38, 35, 29, 26],
                [43, 38, 35, 29, 25],
                [43, 38, 34, 32, 28],
                [43, 38, 34, 32, 27],
                [43, 38, 34, 32, 26],
                [43, 38, 34, 32, 25],
                [43, 38, 34, 31, 28],
                [43, 38, 34, 31, 27],
                [43, 38, 34, 31, 26],
                [43, 38, 34, 31, 25],
                [43, 38, 34, 30, 28],
                [43, 38, 34, 30, 27],
                [43, 38, 34, 30, 26],
                [43, 38, 34, 30, 25],
                [43, 38, 34, 29, 28],
                [43, 38, 34, 29, 27],
                [43, 38, 34, 29, 26],
                [43, 38, 34, 29, 25],
                [43, 38, 33, 32, 28],
                [43, 38, 33, 32, 27],
                [43, 38, 33, 32, 26],
                [43, 38, 33, 32, 25],
                [43, 38, 33, 31, 28],
                [43, 38, 33, 31, 27],
                [43, 38, 33, 31, 26],
                [43, 38, 33, 31, 25],
                [43, 38, 33, 30, 28],
                [43, 38, 33, 30, 27],
                [43, 38, 33, 30, 26],
                [43, 38, 33, 30, 25],
                [43, 38, 33, 29, 28],
                [43, 38, 33, 29, 27],
                [43, 38, 33, 29, 26],
                [43, 38, 33, 29, 25],
                [43, 37, 36, 32, 28],
                [43, 37, 36, 32, 27],
                [43, 37, 36, 32, 26],
                [43, 37, 36, 32, 25],
                [43, 37, 36, 31, 28],
                [43, 37, 36, 31, 27],
                [43, 37, 36, 31, 26],
                [43, 37, 36, 31, 25],
                [43, 37, 36, 30, 28],
                [43, 37, 36, 30, 27],
                [43, 37, 36, 30, 26],
                [43, 37, 36, 30, 25],
                [43, 37, 36, 29, 28],
                [43, 37, 36, 29, 27],
                [43, 37, 36, 29, 26],
                [43, 37, 36, 29, 25],
                [43, 37, 35, 32, 28],
                [43, 37, 35, 32, 27],
                [43, 37, 35, 32, 26],
                [43, 37, 35, 32, 25],
                [43, 37, 35, 31, 28],
                [43, 37, 35, 31, 27],
                [43, 37, 35, 31, 26],
                [43, 37, 35, 31, 25],
                [43, 37, 35, 30, 28],
                [43, 37, 35, 30, 27],
                [43, 37, 35, 30, 26],
                [43, 37, 35, 30, 25],
                [43, 37, 35, 29, 28],
                [43, 37, 35, 29, 27],
                [43, 37, 35, 29, 26],
                [43, 37, 35, 29, 25],
                [43, 37, 34, 32, 28],
                [43, 37, 34, 32, 27],
                [43, 37, 34, 32, 26],
                [43, 37, 34, 32, 25],
                [43, 37, 34, 31, 28],
                [43, 37, 34, 31, 27],
                [43, 37, 34, 31, 26],
                [43, 37, 34, 31, 25],
                [43, 37, 34, 30, 28],
                [43, 37, 34, 30, 27],
                [43, 37, 34, 30, 26],
                [43, 37, 34, 30, 25],
                [43, 37, 34, 29, 28],
                [43, 37, 34, 29, 27],
                [43, 37, 34, 29, 26],
                [43, 37, 34, 29, 25],
                [43, 37, 33, 32, 28],
                [43, 37, 33, 32, 27],
                [43, 37, 33, 32, 26],
                [43, 37, 33, 32, 25],
                [43, 37, 33, 31, 28],
                [43, 37, 33, 31, 27],
                [43, 37, 33, 31, 26],
                [43, 37, 33, 31, 25],
                [43, 37, 33, 30, 28],
                [43, 37, 33, 30, 27],
                [43, 37, 33, 30, 26],
                [43, 37, 33, 30, 25],
                [43, 37, 33, 29, 28],
                [43, 37, 33, 29, 27],
                [43, 37, 33, 29, 26],
                [43, 37, 33, 29, 25],
                [42, 40, 36, 32, 28],
                [42, 40, 36, 32, 27],
                [42, 40, 36, 32, 26],
                [42, 40, 36, 32, 25],
                [42, 40, 36, 31, 28],
                [42, 40, 36, 31, 27],
                [42, 40, 36, 31, 26],
                [42, 40, 36, 31, 25],
                [42, 40, 36, 30, 28],
                [42, 40, 36, 30, 27],
                [42, 40, 36, 30, 26],
                [42, 40, 36, 30, 25],
                [42, 40, 36, 29, 28],
                [42, 40, 36, 29, 27],
                [42, 40, 36, 29, 26],
                [42, 40, 36, 29, 25],
                [42, 40, 35, 32, 28],
                [42, 40, 35, 32, 27],
                [42, 40, 35, 32, 26],
                [42, 40, 35, 32, 25],
                [42, 40, 35, 31, 28],
                [42, 40, 35, 31, 27],
                [42, 40, 35, 31, 26],
                [42, 40, 35, 31, 25],
                [42, 40, 35, 30, 28],
                [42, 40, 35, 30, 27],
                [42, 40, 35, 30, 26],
                [42, 40, 35, 30, 25],
                [42, 40, 35, 29, 28],
                [42, 40, 35, 29, 27],
                [42, 40, 35, 29, 26],
                [42, 40, 35, 29, 25],
                [42, 40, 34, 32, 28],
                [42, 40, 34, 32, 27],
                [42, 40, 34, 32, 26],
                [42, 40, 34, 32, 25],
                [42, 40, 34, 31, 28],
                [42, 40, 34, 31, 27],
                [42, 40, 34, 31, 26],
                [42, 40, 34, 31, 25],
                [42, 40, 34, 30, 28],
                [42, 40, 34, 30, 27],
                [42, 40, 34, 30, 26],
                [42, 40, 34, 30, 25],
                [42, 40, 34, 29, 28],
                [42, 40, 34, 29, 27],
                [42, 40, 34, 29, 26],
                [42, 40, 34, 29, 25],
                [42, 40, 33, 32, 28],
                [42, 40, 33, 32, 27],
                [42, 40, 33, 32, 26],
                [42, 40, 33, 32, 25],
                [42, 40, 33, 31, 28],
                [42, 40, 33, 31, 27],
                [42, 40, 33, 31, 26],
                [42, 40, 33, 31, 25],
                [42, 40, 33, 30, 28],
                [42, 40, 33, 30, 27],
                [42, 40, 33, 30, 26],
                [42, 40, 33, 30, 25],
                [42, 40, 33, 29, 28],
                [42, 40, 33, 29, 27],
                [42, 40, 33, 29, 26],
                [42, 40, 33, 29, 25],
                [42, 39, 36, 32, 28],
                [42, 39, 36, 32, 27],
                [42, 39, 36, 32, 26],
                [42, 39, 36, 32, 25],
                [42, 39, 36, 31, 28],
                [42, 39, 36, 31, 27],
                [42, 39, 36, 31, 26],
                [42, 39, 36, 31, 25],
                [42, 39, 36, 30, 28],
                [42, 39, 36, 30, 27],
                [42, 39, 36, 30, 26],
                [42, 39, 36, 30, 25],
                [42, 39, 36, 29, 28],
                [42, 39, 36, 29, 27],
                [42, 39, 36, 29, 26],
                [42, 39, 36, 29, 25],
                [42, 39, 35, 32, 28],
                [42, 39, 35, 32, 27],
                [42, 39, 35, 32, 26],
                [42, 39, 35, 32, 25],
                [42, 39, 35, 31, 28],
                [42, 39, 35, 31, 27],
                [42, 39, 35, 31, 26],
                [42, 39, 35, 31, 25],
                [42, 39, 35, 30, 28],
                [42, 39, 35, 30, 27],
                [42, 39, 35, 30, 26],
                [42, 39, 35, 30, 25],
                [42, 39, 35, 29, 28],
                [42, 39, 35, 29, 27],
                [42, 39, 35, 29, 26],
                [42, 39, 35, 29, 25],
                [42, 39, 34, 32, 28],
                [42, 39, 34, 32, 27],
                [42, 39, 34, 32, 26],
                [42, 39, 34, 32, 25],
                [42, 39, 34, 31, 28],
                [42, 39, 34, 31, 27],
                [42, 39, 34, 31, 26],
                [42, 39, 34, 31, 25],
                [42, 39, 34, 30, 28],
                [42, 39, 34, 30, 27],
                [42, 39, 34, 30, 26],
                [42, 39, 34, 30, 25],
                [42, 39, 34, 29, 28],
                [42, 39, 34, 29, 27],
                [42, 39, 34, 29, 26],
                [42, 39, 34, 29, 25],
                [42, 39, 33, 32, 28],
                [42, 39, 33, 32, 27],
                [42, 39, 33, 32, 26],
                [42, 39, 33, 32, 25],
                [42, 39, 33, 31, 28],
                [42, 39, 33, 31, 27],
                [42, 39, 33, 31, 26],
                [42, 39, 33, 31, 25],
                [42, 39, 33, 30, 28],
                [42, 39, 33, 30, 27],
                [42, 39, 33, 30, 26],
                [42, 39, 33, 30, 25],
                [42, 39, 33, 29, 28],
                [42, 39, 33, 29, 27],
                [42, 39, 33, 29, 26],
                [42, 39, 33, 29, 25],
                [42, 38, 36, 32, 28],
                [42, 38, 36, 32, 27],
                [42, 38, 36, 32, 26],
                [42, 38, 36, 32, 25],
                [42, 38, 36, 31, 28],
                [42, 38, 36, 31, 27],
                [42, 38, 36, 31, 26],
                [42, 38, 36, 31, 25],
                [42, 38, 36, 30, 28],
                [42, 38, 36, 30, 27],
                [42, 38, 36, 30, 26],
                [42, 38, 36, 30, 25],
                [42, 38, 36, 29, 28],
                [42, 38, 36, 29, 27],
                [42, 38, 36, 29, 26],
                [42, 38, 36, 29, 25],
                [42, 38, 35, 32, 28],
                [42, 38, 35, 32, 27],
                [42, 38, 35, 32, 26],
                [42, 38, 35, 32, 25],
                [42, 38, 35, 31, 28],
                [42, 38, 35, 31, 27],
                [42, 38, 35, 31, 26],
                [42, 38, 35, 31, 25],
                [42, 38, 35, 30, 28],
                [42, 38, 35, 30, 27],
                [42, 38, 35, 30, 26],
                [42, 38, 35, 30, 25],
                [42, 38, 35, 29, 28],
                [42, 38, 35, 29, 27],
                [42, 38, 35, 29, 26],
                [42, 38, 35, 29, 25],
                [42, 38, 34, 32, 28],
                [42, 38, 34, 32, 27],
                [42, 38, 34, 32, 26],
                [42, 38, 34, 32, 25],
                [42, 38, 34, 31, 28],
                [42, 38, 34, 31, 27],
                [42, 38, 34, 31, 26],
                [42, 38, 34, 31, 25],
                [42, 38, 34, 30, 28],
                [42, 38, 34, 30, 27],
                [42, 38, 34, 30, 25],
                [42, 38, 34, 29, 28],
                [42, 38, 34, 29, 27],
                [42, 38, 34, 29, 26],
                [42, 38, 34, 29, 25],
                [42, 38, 33, 32, 28],
                [42, 38, 33, 32, 27],
                [42, 38, 33, 32, 26],
                [42, 38, 33, 32, 25],
                [42, 38, 33, 31, 28],
                [42, 38, 33, 31, 27],
                [42, 38, 33, 31, 26],
                [42, 38, 33, 31, 25],
                [42, 38, 33, 30, 28],
                [42, 38, 33, 30, 27],
                [42, 38, 33, 30, 26],
                [42, 38, 33, 30, 25],
                [42, 38, 33, 29, 28],
                [42, 38, 33, 29, 27],
                [42, 38, 33, 29, 26],
                [42, 38, 33, 29, 25],
                [42, 37, 36, 32, 28],
                [42, 37, 36, 32, 27],
                [42, 37, 36, 32, 26],
                [42, 37, 36, 32, 25],
                [42, 37, 36, 31, 28],
                [42, 37, 36, 31, 27],
                [42, 37, 36, 31, 26],
                [42, 37, 36, 31, 25],
                [42, 37, 36, 30, 28],
                [42, 37, 36, 30, 27],
                [42, 37, 36, 30, 26],
                [42, 37, 36, 30, 25],
                [42, 37, 36, 29, 28],
                [42, 37, 36, 29, 27],
                [42, 37, 36, 29, 26],
                [42, 37, 36, 29, 25],
                [42, 37, 35, 32, 28],
                [42, 37, 35, 32, 27],
                [42, 37, 35, 32, 26],
                [42, 37, 35, 32, 25],
                [42, 37, 35, 31, 28],
                [42, 37, 35, 31, 27],
                [42, 37, 35, 31, 26],
                [42, 37, 35, 31, 25],
                [42, 37, 35, 30, 28],
                [42, 37, 35, 30, 27],
                [42, 37, 35, 30, 26],
                [42, 37, 35, 30, 25],
                [42, 37, 35, 29, 28],
                [42, 37, 35, 29, 27],
                [42, 37, 35, 29, 26],
                [42, 37, 35, 29, 25],
                [42, 37, 34, 32, 28],
                [42, 37, 34, 32, 27],
                [42, 37, 34, 32, 26],
                [42, 37, 34, 32, 25],
                [42, 37, 34, 31, 28],
                [42, 37, 34, 31, 27],
                [42, 37, 34, 31, 26],
                [42, 37, 34, 31, 25],
                [42, 37, 34, 30, 28],
                [42, 37, 34, 30, 27],
                [42, 37, 34, 30, 26],
                [42, 37, 34, 30, 25],
                [42, 37, 34, 29, 28],
                [42, 37, 34, 29, 27],
                [42, 37, 34, 29, 26],
                [42, 37, 34, 29, 25],
                [42, 37, 33, 32, 28],
                [42, 37, 33, 32, 27],
                [42, 37, 33, 32, 26],
                [42, 37, 33, 32, 25],
                [42, 37, 33, 31, 28],
                [42, 37, 33, 31, 27],
                [42, 37, 33, 31, 26],
                [42, 37, 33, 31, 25],
                [42, 37, 33, 30, 28],
                [42, 37, 33, 30, 27],
                [42, 37, 33, 30, 26],
                [42, 37, 33, 30, 25],
                [42, 37, 33, 29, 28],
                [42, 37, 33, 29, 27],
                [42, 37, 33, 29, 26],
                [42, 37, 33, 29, 25],
                [41, 40, 36, 32, 28],
                [41, 40, 36, 32, 27],
                [41, 40, 36, 32, 26],
                [41, 40, 36, 32, 25],
                [41, 40, 36, 31, 28],
                [41, 40, 36, 31, 27],
                [41, 40, 36, 31, 26],
                [41, 40, 36, 31, 25],
                [41, 40, 36, 30, 28],
                [41, 40, 36, 30, 27],
                [41, 40, 36, 30, 26],
                [41, 40, 36, 30, 25],
                [41, 40, 36, 29, 28],
                [41, 40, 36, 29, 27],
                [41, 40, 36, 29, 26],
                [41, 40, 36, 29, 25],
                [41, 40, 35, 32, 28],
                [41, 40, 35, 32, 27],
                [41, 40, 35, 32, 26],
                [41, 40, 35, 32, 25],
                [41, 40, 35, 31, 28],
                [41, 40, 35, 31, 27],
                [41, 40, 35, 31, 26],
                [41, 40, 35, 31, 25],
                [41, 40, 35, 30, 28],
                [41, 40, 35, 30, 27],
                [41, 40, 35, 30, 26],
                [41, 40, 35, 30, 25],
                [41, 40, 35, 29, 28],
                [41, 40, 35, 29, 27],
                [41, 40, 35, 29, 26],
                [41, 40, 35, 29, 25],
                [41, 40, 34, 32, 28],
                [41, 40, 34, 32, 27],
                [41, 40, 34, 32, 26],
                [41, 40, 34, 32, 25],
                [41, 40, 34, 31, 28],
                [41, 40, 34, 31, 27],
                [41, 40, 34, 31, 26],
                [41, 40, 34, 31, 25],
                [41, 40, 34, 30, 28],
                [41, 40, 34, 30, 27],
                [41, 40, 34, 30, 26],
                [41, 40, 34, 30, 25],
                [41, 40, 34, 29, 28],
                [41, 40, 34, 29, 27],
                [41, 40, 34, 29, 26],
                [41, 40, 34, 29, 25],
                [41, 40, 33, 32, 28],
                [41, 40, 33, 32, 27],
                [41, 40, 33, 32, 26],
                [41, 40, 33, 32, 25],
                [41, 40, 33, 31, 28],
                [41, 40, 33, 31, 27],
                [41, 40, 33, 31, 26],
                [41, 40, 33, 31, 25],
                [41, 40, 33, 30, 28],
                [41, 40, 33, 30, 27],
                [41, 40, 33, 30, 26],
                [41, 40, 33, 30, 25],
                [41, 40, 33, 29, 28],
                [41, 40, 33, 29, 27],
                [41, 40, 33, 29, 26],
                [41, 40, 33, 29, 25],
                [41, 39, 36, 32, 28],
                [41, 39, 36, 32, 27],
                [41, 39, 36, 32, 26],
                [41, 39, 36, 32, 25],
                [41, 39, 36, 31, 28],
                [41, 39, 36, 31, 27],
                [41, 39, 36, 31, 26],
                [41, 39, 36, 31, 25],
                [41, 39, 36, 30, 28],
                [41, 39, 36, 30, 27],
                [41, 39, 36, 30, 26],
                [41, 39, 36, 30, 25],
                [41, 39, 36, 29, 28],
                [41, 39, 36, 29, 27],
                [41, 39, 36, 29, 26],
                [41, 39, 36, 29, 25],
                [41, 39, 35, 32, 28],
                [41, 39, 35, 32, 27],
                [41, 39, 35, 32, 26],
                [41, 39, 35, 32, 25],
                [41, 39, 35, 31, 28],
                [41, 39, 35, 31, 27],
                [41, 39, 35, 31, 26],
                [41, 39, 35, 31, 25],
                [41, 39, 35, 30, 28],
                [41, 39, 35, 30, 27],
                [41, 39, 35, 30, 26],
                [41, 39, 35, 30, 25],
                [41, 39, 35, 29, 28],
                [41, 39, 35, 29, 27],
                [41, 39, 35, 29, 26],
                [41, 39, 35, 29, 25],
                [41, 39, 34, 32, 28],
                [41, 39, 34, 32, 27],
                [41, 39, 34, 32, 26],
                [41, 39, 34, 32, 25],
                [41, 39, 34, 31, 28],
                [41, 39, 34, 31, 27],
                [41, 39, 34, 31, 26],
                [41, 39, 34, 31, 25],
                [41, 39, 34, 30, 28],
                [41, 39, 34, 30, 27],
                [41, 39, 34, 30, 26],
                [41, 39, 34, 30, 25],
                [41, 39, 34, 29, 28],
                [41, 39, 34, 29, 27],
                [41, 39, 34, 29, 26],
                [41, 39, 34, 29, 25],
                [41, 39, 33, 32, 28],
                [41, 39, 33, 32, 27],
                [41, 39, 33, 32, 26],
                [41, 39, 33, 32, 25],
                [41, 39, 33, 31, 28],
                [41, 39, 33, 31, 27],
                [41, 39, 33, 31, 26],
                [41, 39, 33, 31, 25],
                [41, 39, 33, 30, 28],
                [41, 39, 33, 30, 27],
                [41, 39, 33, 30, 26],
                [41, 39, 33, 30, 25],
                [41, 39, 33, 29, 28],
                [41, 39, 33, 29, 27],
                [41, 39, 33, 29, 26],
                [41, 39, 33, 29, 25],
                [41, 38, 36, 32, 28],
                [41, 38, 36, 32, 27],
                [41, 38, 36, 32, 26],
                [41, 38, 36, 32, 25],
                [41, 38, 36, 31, 28],
                [41, 38, 36, 31, 27],
                [41, 38, 36, 31, 26],
                [41, 38, 36, 31, 25],
                [41, 38, 36, 30, 28],
                [41, 38, 36, 30, 27],
                [41, 38, 36, 30, 26],
                [41, 38, 36, 30, 25],
                [41, 38, 36, 29, 28],
                [41, 38, 36, 29, 27],
                [41, 38, 36, 29, 26],
                [41, 38, 36, 29, 25],
                [41, 38, 35, 32, 28],
                [41, 38, 35, 32, 27],
                [41, 38, 35, 32, 26],
                [41, 38, 35, 32, 25],
                [41, 38, 35, 31, 28],
                [41, 38, 35, 31, 27],
                [41, 38, 35, 31, 26],
                [41, 38, 35, 31, 25],
                [41, 38, 35, 30, 28],
                [41, 38, 35, 30, 27],
                [41, 38, 35, 30, 26],
                [41, 38, 35, 30, 25],
                [41, 38, 35, 29, 28],
                [41, 38, 35, 29, 27],
                [41, 38, 35, 29, 26],
                [41, 38, 35, 29, 25],
                [41, 38, 34, 32, 28],
                [41, 38, 34, 32, 27],
                [41, 38, 34, 32, 26],
                [41, 38, 34, 32, 25],
                [41, 38, 34, 31, 28],
                [41, 38, 34, 31, 27],
                [41, 38, 34, 31, 26],
                [41, 38, 34, 31, 25],
                [41, 38, 34, 30, 28],
                [41, 38, 34, 30, 27],
                [41, 38, 34, 30, 26],
                [41, 38, 34, 30, 25],
                [41, 38, 34, 29, 28],
                [41, 38, 34, 29, 27],
                [41, 38, 34, 29, 26],
                [41, 38, 34, 29, 25],
                [41, 38, 33, 32, 28],
                [41, 38, 33, 32, 27],
                [41, 38, 33, 32, 26],
                [41, 38, 33, 32, 25],
                [41, 38, 33, 31, 28],
                [41, 38, 33, 31, 27],
                [41, 38, 33, 31, 26],
                [41, 38, 33, 31, 25],
                [41, 38, 33, 30, 28],
                [41, 38, 33, 30, 27],
                [41, 38, 33, 30, 26],
                [41, 38, 33, 30, 25],
                [41, 38, 33, 29, 28],
                [41, 38, 33, 29, 27],
                [41, 38, 33, 29, 26],
                [41, 38, 33, 29, 25],
                [41, 37, 36, 32, 28],
                [41, 37, 36, 32, 27],
                [41, 37, 36, 32, 26],
                [41, 37, 36, 32, 25],
                [41, 37, 36, 31, 28],
                [41, 37, 36, 31, 27],
                [41, 37, 36, 31, 26],
                [41, 37, 36, 31, 25],
                [41, 37, 36, 30, 28],
                [41, 37, 36, 30, 27],
                [41, 37, 36, 30, 26],
                [41, 37, 36, 30, 25],
                [41, 37, 36, 29, 28],
                [41, 37, 36, 29, 27],
                [41, 37, 36, 29, 26],
                [41, 37, 36, 29, 25],
                [41, 37, 35, 32, 28],
                [41, 37, 35, 32, 27],
                [41, 37, 35, 32, 26],
                [41, 37, 35, 32, 25],
                [41, 37, 35, 31, 28],
                [41, 37, 35, 31, 27],
                [41, 37, 35, 31, 26],
                [41, 37, 35, 31, 25],
                [41, 37, 35, 30, 28],
                [41, 37, 35, 30, 27],
                [41, 37, 35, 30, 26],
                [41, 37, 35, 30, 25],
                [41, 37, 35, 29, 28],
                [41, 37, 35, 29, 27],
                [41, 37, 35, 29, 26],
                [41, 37, 35, 29, 25],
                [41, 37, 34, 32, 28],
                [41, 37, 34, 32, 27],
                [41, 37, 34, 32, 26],
                [41, 37, 34, 32, 25],
                [41, 37, 34, 31, 28],
                [41, 37, 34, 31, 27],
                [41, 37, 34, 31, 26],
                [41, 37, 34, 31, 25],
                [41, 37, 34, 30, 28],
                [41, 37, 34, 30, 27],
                [41, 37, 34, 30, 26],
                [41, 37, 34, 30, 25],
                [41, 37, 34, 29, 28],
                [41, 37, 34, 29, 27],
                [41, 37, 34, 29, 26],
                [41, 37, 34, 29, 25],
                [41, 37, 33, 32, 28],
                [41, 37, 33, 32, 27],
                [41, 37, 33, 32, 26],
                [41, 37, 33, 32, 25],
                [41, 37, 33, 31, 28],
                [41, 37, 33, 31, 27],
                [41, 37, 33, 31, 26],
                [41, 37, 33, 31, 25],
                [41, 37, 33, 30, 28],
                [41, 37, 33, 30, 27],
                [41, 37, 33, 30, 26],
                [41, 37, 33, 30, 25],
                [41, 37, 33, 29, 28],
                [41, 37, 33, 29, 27],
                [41, 37, 33, 29, 26],
                [40, 36, 32, 28, 23],
                [40, 36, 32, 28, 22],
                [40, 36, 32, 28, 21],
                [40, 36, 32, 27, 24],
                [40, 36, 32, 27, 23],
                [40, 36, 32, 27, 22],
                [40, 36, 32, 27, 21],
                [40, 36, 32, 26, 24],
                [40, 36, 32, 26, 23],
                [40, 36, 32, 26, 22],
                [40, 36, 32, 26, 21],
                [40, 36, 32, 25, 24],
                [40, 36, 32, 25, 23],
                [40, 36, 32, 25, 22],
                [40, 36, 32, 25, 21],
                [40, 36, 31, 28, 24],
                [40, 36, 31, 28, 23],
                [40, 36, 31, 28, 22],
                [40, 36, 31, 28, 21],
                [40, 36, 31, 27, 24],
                [40, 36, 31, 27, 23],
                [40, 36, 31, 27, 22],
                [40, 36, 31, 27, 21],
                [40, 36, 31, 26, 24],
                [40, 36, 31, 26, 23],
                [40, 36, 31, 26, 22],
                [40, 36, 31, 26, 21],
                [40, 36, 31, 25, 24],
                [40, 36, 31, 25, 23],
                [40, 36, 31, 25, 22],
                [40, 36, 31, 25, 21],
                [40, 36, 30, 28, 24],
                [40, 36, 30, 28, 23],
                [40, 36, 30, 28, 22],
                [40, 36, 30, 28, 21],
                [40, 36, 30, 27, 24],
                [40, 36, 30, 27, 23],
                [40, 36, 30, 27, 22],
                [40, 36, 30, 27, 21],
                [40, 36, 30, 26, 24],
                [40, 36, 30, 26, 23],
                [40, 36, 30, 26, 22],
                [40, 36, 30, 26, 21],
                [40, 36, 30, 25, 24],
                [40, 36, 30, 25, 23],
                [40, 36, 30, 25, 22],
                [40, 36, 30, 25, 21],
                [40, 36, 29, 28, 24],
                [40, 36, 29, 28, 23],
                [40, 36, 29, 28, 22],
                [40, 36, 29, 28, 21],
                [40, 36, 29, 27, 24],
                [40, 36, 29, 27, 23],
                [40, 36, 29, 27, 22],
                [40, 36, 29, 27, 21],
                [40, 36, 29, 26, 24],
                [40, 36, 29, 26, 23],
                [40, 36, 29, 26, 22],
                [40, 36, 29, 26, 21],
                [40, 36, 29, 25, 24],
                [40, 36, 29, 25, 23],
                [40, 36, 29, 25, 22],
                [40, 36, 29, 25, 21],
                [40, 35, 32, 28, 24],
                [40, 35, 32, 28, 23],
                [40, 35, 32, 28, 22],
                [40, 35, 32, 28, 21],
                [40, 35, 32, 27, 24],
                [40, 35, 32, 27, 23],
                [40, 35, 32, 27, 22],
                [40, 35, 32, 27, 21],
                [40, 35, 32, 26, 24],
                [40, 35, 32, 26, 23],
                [40, 35, 32, 26, 22],
                [40, 35, 32, 26, 21],
                [40, 35, 32, 25, 24],
                [40, 35, 32, 25, 23],
                [40, 35, 32, 25, 22],
                [40, 35, 32, 25, 21],
                [40, 35, 31, 28, 24],
                [40, 35, 31, 28, 23],
                [40, 35, 31, 28, 22],
                [40, 35, 31, 28, 21],
                [40, 35, 31, 27, 24],
                [40, 35, 31, 27, 23],
                [40, 35, 31, 27, 22],
                [40, 35, 31, 27, 21],
                [40, 35, 31, 26, 24],
                [40, 35, 31, 26, 23],
                [40, 35, 31, 26, 22],
                [40, 35, 31, 26, 21],
                [40, 35, 31, 25, 24],
                [40, 35, 31, 25, 23],
                [40, 35, 31, 25, 22],
                [40, 35, 31, 25, 21],
                [40, 35, 30, 28, 24],
                [40, 35, 30, 28, 23],
                [40, 35, 30, 28, 22],
                [40, 35, 30, 28, 21],
                [40, 35, 30, 27, 24],
                [40, 35, 30, 27, 23],
                [40, 35, 30, 27, 22],
                [40, 35, 30, 27, 21],
                [40, 35, 30, 26, 24],
                [40, 35, 30, 26, 23],
                [40, 35, 30, 26, 22],
                [40, 35, 30, 26, 21],
                [40, 35, 30, 25, 24],
                [40, 35, 30, 25, 23],
                [40, 35, 30, 25, 22],
                [40, 35, 30, 25, 21],
                [40, 35, 29, 28, 24],
                [40, 35, 29, 28, 23],
                [40, 35, 29, 28, 22],
                [40, 35, 29, 28, 21],
                [40, 35, 29, 27, 24],
                [40, 35, 29, 27, 23],
                [40, 35, 29, 27, 22],
                [40, 35, 29, 27, 21],
                [40, 35, 29, 26, 24],
                [40, 35, 29, 26, 23],
                [40, 35, 29, 26, 22],
                [40, 35, 29, 26, 21],
                [40, 35, 29, 25, 24],
                [40, 35, 29, 25, 23],
                [40, 35, 29, 25, 22],
                [40, 35, 29, 25, 21],
                [40, 34, 32, 28, 24],
                [40, 34, 32, 28, 23],
                [40, 34, 32, 28, 22],
                [40, 34, 32, 28, 21],
                [40, 34, 32, 27, 24],
                [40, 34, 32, 27, 23],
                [40, 34, 32, 27, 22],
                [40, 34, 32, 27, 21],
                [40, 34, 32, 26, 24],
                [40, 34, 32, 26, 23],
                [40, 34, 32, 26, 22],
                [40, 34, 32, 26, 21],
                [40, 34, 32, 25, 24],
                [40, 34, 32, 25, 23],
                [40, 34, 32, 25, 22],
                [40, 34, 32, 25, 21],
                [40, 34, 31, 28, 24],
                [40, 34, 31, 28, 23],
                [40, 34, 31, 28, 22],
                [40, 34, 31, 28, 21],
                [40, 34, 31, 27, 24],
                [40, 34, 31, 27, 23],
                [40, 34, 31, 27, 22],
                [40, 34, 31, 27, 21],
                [40, 34, 31, 26, 24],
                [40, 34, 31, 26, 23],
                [40, 34, 31, 26, 22],
                [40, 34, 31, 26, 21],
                [40, 34, 31, 25, 24],
                [40, 34, 31, 25, 23],
                [40, 34, 31, 25, 22],
                [40, 34, 31, 25, 21],
                [40, 34, 30, 28, 24],
                [40, 34, 30, 28, 23],
                [40, 34, 30, 28, 22],
                [40, 34, 30, 28, 21],
                [40, 34, 30, 27, 24],
                [40, 34, 30, 27, 23],
                [40, 34, 30, 27, 22],
                [40, 34, 30, 27, 21],
                [40, 34, 30, 26, 24],
                [40, 34, 30, 26, 23],
                [40, 34, 30, 26, 22],
                [40, 34, 30, 26, 21],
                [40, 34, 30, 25, 24],
                [40, 34, 30, 25, 23],
                [40, 34, 30, 25, 22],
                [40, 34, 30, 25, 21],
                [40, 34, 29, 28, 24],
                [40, 34, 29, 28, 23],
                [40, 34, 29, 28, 22],
                [40, 34, 29, 28, 21],
                [40, 34, 29, 27, 24],
                [40, 34, 29, 27, 23],
                [40, 34, 29, 27, 22],
                [40, 34, 29, 27, 21],
                [40, 34, 29, 26, 24],
                [40, 34, 29, 26, 23],
                [40, 34, 29, 26, 22],
                [40, 34, 29, 26, 21],
                [40, 34, 29, 25, 24],
                [40, 34, 29, 25, 23],
                [40, 34, 29, 25, 22],
                [40, 34, 29, 25, 21],
                [40, 33, 32, 28, 24],
                [40, 33, 32, 28, 23],
                [40, 33, 32, 28, 22],
                [40, 33, 32, 28, 21],
                [40, 33, 32, 27, 24],
                [40, 33, 32, 27, 23],
                [40, 33, 32, 27, 22],
                [40, 33, 32, 27, 21],
                [40, 33, 32, 26, 24],
                [40, 33, 32, 26, 23],
                [40, 33, 32, 26, 22],
                [40, 33, 32, 26, 21],
                [40, 33, 32, 25, 24],
                [40, 33, 32, 25, 23],
                [40, 33, 32, 25, 22],
                [40, 33, 32, 25, 21],
                [40, 33, 31, 28, 24],
                [40, 33, 31, 28, 23],
                [40, 33, 31, 28, 22],
                [40, 33, 31, 28, 21],
                [40, 33, 31, 27, 24],
                [40, 33, 31, 27, 23],
                [40, 33, 31, 27, 22],
                [40, 33, 31, 27, 21],
                [40, 33, 31, 26, 24],
                [40, 33, 31, 26, 23],
                [40, 33, 31, 26, 22],
                [40, 33, 31, 26, 21],
                [40, 33, 31, 25, 24],
                [40, 33, 31, 25, 23],
                [40, 33, 31, 25, 22],
                [40, 33, 31, 25, 21],
                [40, 33, 30, 28, 24],
                [40, 33, 30, 28, 23],
                [40, 33, 30, 28, 22],
                [40, 33, 30, 28, 21],
                [40, 33, 30, 27, 24],
                [40, 33, 30, 27, 23],
                [40, 33, 30, 27, 22],
                [40, 33, 30, 27, 21],
                [40, 33, 30, 26, 24],
                [40, 33, 30, 26, 23],
                [40, 33, 30, 26, 22],
                [40, 33, 30, 26, 21],
                [40, 33, 30, 25, 24],
                [40, 33, 30, 25, 23],
                [40, 33, 30, 25, 22],
                [40, 33, 30, 25, 21],
                [40, 33, 29, 28, 24],
                [40, 33, 29, 28, 23],
                [40, 33, 29, 28, 22],
                [40, 33, 29, 28, 21],
                [40, 33, 29, 27, 24],
                [40, 33, 29, 27, 23],
                [40, 33, 29, 27, 22],
                [40, 33, 29, 27, 21],
                [40, 33, 29, 26, 24],
                [40, 33, 29, 26, 23],
                [40, 33, 29, 26, 22],
                [40, 33, 29, 26, 21],
                [40, 33, 29, 25, 24],
                [40, 33, 29, 25, 23],
                [40, 33, 29, 25, 22],
                [40, 33, 29, 25, 21],
                [39, 36, 32, 28, 24],
                [39, 36, 32, 28, 23],
                [39, 36, 32, 28, 22],
                [39, 36, 32, 28, 21],
                [39, 36, 32, 27, 24],
                [39, 36, 32, 27, 23],
                [39, 36, 32, 27, 22],
                [39, 36, 32, 27, 21],
                [39, 36, 32, 26, 24],
                [39, 36, 32, 26, 23],
                [39, 36, 32, 26, 22],
                [39, 36, 32, 26, 21],
                [39, 36, 32, 25, 24],
                [39, 36, 32, 25, 23],
                [39, 36, 32, 25, 22],
                [39, 36, 32, 25, 21],
                [39, 36, 31, 28, 24],
                [39, 36, 31, 28, 23],
                [39, 36, 31, 28, 22],
                [39, 36, 31, 28, 21],
                [39, 36, 31, 27, 24],
                [39, 36, 31, 27, 23],
                [39, 36, 31, 27, 22],
                [39, 36, 31, 27, 21],
                [39, 36, 31, 26, 24],
                [39, 36, 31, 26, 23],
                [39, 36, 31, 26, 22],
                [39, 36, 31, 26, 21],
                [39, 36, 31, 25, 24],
                [39, 36, 31, 25, 23],
                [39, 36, 31, 25, 22],
                [39, 36, 31, 25, 21],
                [39, 36, 30, 28, 24],
                [39, 36, 30, 28, 23],
                [39, 36, 30, 28, 22],
                [39, 36, 30, 28, 21],
                [39, 36, 30, 27, 24],
                [39, 36, 30, 27, 23],
                [39, 36, 30, 27, 22],
                [39, 36, 30, 27, 21],
                [39, 36, 30, 26, 24],
                [39, 36, 30, 26, 23],
                [39, 36, 30, 26, 22],
                [39, 36, 30, 26, 21],
                [39, 36, 30, 25, 24],
                [39, 36, 30, 25, 23],
                [39, 36, 30, 25, 22],
                [39, 36, 30, 25, 21],
                [39, 36, 29, 28, 24],
                [39, 36, 29, 28, 23],
                [39, 36, 29, 28, 22],
                [39, 36, 29, 28, 21],
                [39, 36, 29, 27, 24],
                [39, 36, 29, 27, 23],
                [39, 36, 29, 27, 22],
                [39, 36, 29, 27, 21],
                [39, 36, 29, 26, 24],
                [39, 36, 29, 26, 23],
                [39, 36, 29, 26, 22],
                [39, 36, 29, 26, 21],
                [39, 36, 29, 25, 24],
                [39, 36, 29, 25, 23],
                [39, 36, 29, 25, 22],
                [39, 36, 29, 25, 21],
                [39, 35, 32, 28, 24],
                [39, 35, 32, 28, 23],
                [39, 35, 32, 28, 22],
                [39, 35, 32, 28, 21],
                [39, 35, 32, 27, 24],
                [39, 35, 32, 27, 23],
                [39, 35, 32, 27, 22],
                [39, 35, 32, 27, 21],
                [39, 35, 32, 26, 24],
                [39, 35, 32, 26, 23],
                [39, 35, 32, 26, 22],
                [39, 35, 32, 26, 21],
                [39, 35, 32, 25, 24],
                [39, 35, 32, 25, 23],
                [39, 35, 32, 25, 22],
                [39, 35, 32, 25, 21],
                [39, 35, 31, 28, 24],
                [39, 35, 31, 28, 23],
                [39, 35, 31, 28, 22],
                [39, 35, 31, 28, 21],
                [39, 35, 31, 27, 24],
                [39, 35, 31, 27, 22],
                [39, 35, 31, 27, 21],
                [39, 35, 31, 26, 24],
                [39, 35, 31, 26, 23],
                [39, 35, 31, 26, 22],
                [39, 35, 31, 26, 21],
                [39, 35, 31, 25, 24],
                [39, 35, 31, 25, 23],
                [39, 35, 31, 25, 22],
                [39, 35, 31, 25, 21],
                [39, 35, 30, 28, 24],
                [39, 35, 30, 28, 23],
                [39, 35, 30, 28, 22],
                [39, 35, 30, 28, 21],
                [39, 35, 30, 27, 24],
                [39, 35, 30, 27, 23],
                [39, 35, 30, 27, 22],
                [39, 35, 30, 27, 21],
                [39, 35, 30, 26, 24],
                [39, 35, 30, 26, 23],
                [39, 35, 30, 26, 22],
                [39, 35, 30, 26, 21],
                [39, 35, 30, 25, 24],
                [39, 35, 30, 25, 23],
                [39, 35, 30, 25, 22],
                [39, 35, 30, 25, 21],
                [39, 35, 29, 28, 24],
                [39, 35, 29, 28, 23],
                [39, 35, 29, 28, 22],
                [39, 35, 29, 28, 21],
                [39, 35, 29, 27, 24],
                [39, 35, 29, 27, 23],
                [39, 35, 29, 27, 22],
                [39, 35, 29, 27, 21],
                [39, 35, 29, 26, 24],
                [39, 35, 29, 26, 23],
                [39, 35, 29, 26, 22],
                [39, 35, 29, 26, 21],
                [39, 35, 29, 25, 24],
                [39, 35, 29, 25, 23],
                [39, 35, 29, 25, 22],
                [39, 35, 29, 25, 21],
                [39, 34, 32, 28, 24],
                [39, 34, 32, 28, 23],
                [39, 34, 32, 28, 22],
                [39, 34, 32, 28, 21],
                [39, 34, 32, 27, 24],
                [39, 34, 32, 27, 23],
                [39, 34, 32, 27, 22],
                [39, 34, 32, 27, 21],
                [39, 34, 32, 26, 24],
                [39, 34, 32, 26, 23],
                [39, 34, 32, 26, 22],
                [39, 34, 32, 26, 21],
                [39, 34, 32, 25, 24],
                [39, 34, 32, 25, 23],
                [39, 34, 32, 25, 22],
                [39, 34, 32, 25, 21],
                [39, 34, 31, 28, 24],
                [39, 34, 31, 28, 23],
                [39, 34, 31, 28, 22],
                [39, 34, 31, 28, 21],
                [39, 34, 31, 27, 24],
                [39, 34, 31, 27, 23],
                [39, 34, 31, 27, 22],
                [39, 34, 31, 27, 21],
                [39, 34, 31, 26, 24],
                [39, 34, 31, 26, 23],
                [39, 34, 31, 26, 22],
                [39, 34, 31, 26, 21],
                [39, 34, 31, 25, 24],
                [39, 34, 31, 25, 23],
                [39, 34, 31, 25, 22],
                [39, 34, 31, 25, 21],
                [39, 34, 30, 28, 24],
                [39, 34, 30, 28, 23],
                [39, 34, 30, 28, 22],
                [39, 34, 30, 28, 21],
                [39, 34, 30, 27, 24],
                [39, 34, 30, 27, 23],
                [39, 34, 30, 27, 22],
                [39, 34, 30, 27, 21],
                [39, 34, 30, 26, 24],
                [39, 34, 30, 26, 23],
                [39, 34, 30, 26, 22],
                [39, 34, 30, 26, 21],
                [39, 34, 30, 25, 24],
                [39, 34, 30, 25, 23],
                [39, 34, 30, 25, 22],
                [39, 34, 30, 25, 21],
                [39, 34, 29, 28, 24],
                [39, 34, 29, 28, 23],
                [39, 34, 29, 28, 22],
                [39, 34, 29, 28, 21],
                [39, 34, 29, 27, 24],
                [39, 34, 29, 27, 23],
                [39, 34, 29, 27, 22],
                [39, 34, 29, 27, 21],
                [39, 34, 29, 26, 24],
                [39, 34, 29, 26, 23],
                [39, 34, 29, 26, 22],
                [39, 34, 29, 26, 21],
                [39, 34, 29, 25, 24],
                [39, 34, 29, 25, 23],
                [39, 34, 29, 25, 22],
                [39, 34, 29, 25, 21],
                [39, 33, 32, 28, 24],
                [39, 33, 32, 28, 23],
                [39, 33, 32, 28, 22],
                [39, 33, 32, 28, 21],
                [39, 33, 32, 27, 24],
                [39, 33, 32, 27, 23],
                [39, 33, 32, 27, 22],
                [39, 33, 32, 27, 21],
                [39, 33, 32, 26, 24],
                [39, 33, 32, 26, 23],
                [39, 33, 32, 26, 22],
                [39, 33, 32, 26, 21],
                [39, 33, 32, 25, 24],
                [39, 33, 32, 25, 23],
                [39, 33, 32, 25, 22],
                [39, 33, 32, 25, 21],
                [39, 33, 31, 28, 24],
                [39, 33, 31, 28, 23],
                [39, 33, 31, 28, 22],
                [39, 33, 31, 28, 21],
                [39, 33, 31, 27, 24],
                [39, 33, 31, 27, 23],
                [39, 33, 31, 27, 22],
                [39, 33, 31, 27, 21],
                [39, 33, 31, 26, 24],
                [39, 33, 31, 26, 23],
                [39, 33, 31, 26, 22],
                [39, 33, 31, 26, 21],
                [39, 33, 31, 25, 24],
                [39, 33, 31, 25, 23],
                [39, 33, 31, 25, 22],
                [39, 33, 31, 25, 21],
                [39, 33, 30, 28, 24],
                [39, 33, 30, 28, 23],
                [39, 33, 30, 28, 22],
                [39, 33, 30, 28, 21],
                [39, 33, 30, 27, 24],
                [39, 33, 30, 27, 23],
                [39, 33, 30, 27, 22],
                [39, 33, 30, 27, 21],
                [39, 33, 30, 26, 24],
                [39, 33, 30, 26, 23],
                [39, 33, 30, 26, 22],
                [39, 33, 30, 26, 21],
                [39, 33, 30, 25, 24],
                [39, 33, 30, 25, 23],
                [39, 33, 30, 25, 22],
                [39, 33, 30, 25, 21],
                [39, 33, 29, 28, 24],
                [39, 33, 29, 28, 23],
                [39, 33, 29, 28, 22],
                [39, 33, 29, 28, 21],
                [39, 33, 29, 27, 24],
                [39, 33, 29, 27, 23],
                [39, 33, 29, 27, 22],
                [39, 33, 29, 27, 21],
                [39, 33, 29, 26, 24],
                [39, 33, 29, 26, 23],
                [39, 33, 29, 26, 22],
                [39, 33, 29, 26, 21],
                [39, 33, 29, 25, 24],
                [39, 33, 29, 25, 23],
                [39, 33, 29, 25, 22],
                [39, 33, 29, 25, 21],
                [38, 36, 32, 28, 24],
                [38, 36, 32, 28, 23],
                [38, 36, 32, 28, 22],
                [38, 36, 32, 28, 21],
                [38, 36, 32, 27, 24],
                [38, 36, 32, 27, 23],
                [38, 36, 32, 27, 22],
                [38, 36, 32, 27, 21],
                [38, 36, 32, 26, 24],
                [38, 36, 32, 26, 23],
                [38, 36, 32, 26, 22],
                [38, 36, 32, 26, 21],
                [38, 36, 32, 25, 24],
                [38, 36, 32, 25, 23],
                [38, 36, 32, 25, 22],
                [38, 36, 32, 25, 21],
                [38, 36, 31, 28, 24],
                [38, 36, 31, 28, 23],
                [38, 36, 31, 28, 22],
                [38, 36, 31, 28, 21],
                [38, 36, 31, 27, 24],
                [38, 36, 31, 27, 23],
                [38, 36, 31, 27, 22],
                [38, 36, 31, 27, 21],
                [38, 36, 31, 26, 24],
                [38, 36, 31, 26, 23],
                [38, 36, 31, 26, 22],
                [38, 36, 31, 26, 21],
                [38, 36, 31, 25, 24],
                [38, 36, 31, 25, 23],
                [38, 36, 31, 25, 22],
                [38, 36, 31, 25, 21],
                [38, 36, 30, 28, 24],
                [38, 36, 30, 28, 23],
                [38, 36, 30, 28, 22],
                [38, 36, 30, 28, 21],
                [38, 36, 30, 27, 24],
                [38, 36, 30, 27, 23],
                [38, 36, 30, 27, 22],
                [38, 36, 30, 27, 21],
                [38, 36, 30, 26, 24],
                [38, 36, 30, 26, 23],
                [38, 36, 30, 26, 22],
                [38, 36, 30, 26, 21],
                [38, 36, 30, 25, 24],
                [38, 36, 30, 25, 23],
                [38, 36, 30, 25, 22],
                [38, 36, 30, 25, 21],
                [38, 36, 29, 28, 24],
                [38, 36, 29, 28, 23],
                [38, 36, 29, 28, 22],
                [38, 36, 29, 28, 21],
                [38, 36, 29, 27, 24],
                [38, 36, 29, 27, 23],
                [38, 36, 29, 27, 22],
                [38, 36, 29, 27, 21],
                [38, 36, 29, 26, 24],
                [38, 36, 29, 26, 23],
                [38, 36, 29, 26, 22],
                [38, 36, 29, 26, 21],
                [38, 36, 29, 25, 24],
                [38, 36, 29, 25, 23],
                [38, 36, 29, 25, 22],
                [38, 36, 29, 25, 21],
                [38, 35, 32, 28, 24],
                [38, 35, 32, 28, 23],
                [38, 35, 32, 28, 22],
                [38, 35, 32, 28, 21],
                [38, 35, 32, 27, 24],
                [38, 35, 32, 27, 23],
                [38, 35, 32, 27, 22],
                [38, 35, 32, 27, 21],
                [38, 35, 32, 26, 24],
                [38, 35, 32, 26, 23],
                [38, 35, 32, 26, 22],
                [38, 35, 32, 26, 21],
                [38, 35, 32, 25, 24],
                [38, 35, 32, 25, 23],
                [38, 35, 32, 25, 22],
                [38, 35, 32, 25, 21],
                [38, 35, 31, 28, 24],
                [38, 35, 31, 28, 23],
                [38, 35, 31, 28, 22],
                [38, 35, 31, 28, 21],
                [38, 35, 31, 27, 24],
                [38, 35, 31, 27, 23],
                [38, 35, 31, 27, 22],
                [38, 35, 31, 27, 21],
                [38, 35, 31, 26, 24],
                [38, 35, 31, 26, 23],
                [38, 35, 31, 26, 22],
                [38, 35, 31, 26, 21],
                [38, 35, 31, 25, 24],
                [38, 35, 31, 25, 23],
                [38, 35, 31, 25, 22],
                [38, 35, 31, 25, 21],
                [38, 35, 30, 28, 24],
                [38, 35, 30, 28, 23],
                [38, 35, 30, 28, 22],
                [38, 35, 30, 28, 21],
                [38, 35, 30, 27, 24],
                [38, 35, 30, 27, 23],
                [38, 35, 30, 27, 22],
                [38, 35, 30, 27, 21],
                [38, 35, 30, 26, 24],
                [38, 35, 30, 26, 23],
                [38, 35, 30, 26, 22],
                [38, 35, 30, 26, 21],
                [38, 35, 30, 25, 24],
                [38, 35, 30, 25, 23],
                [38, 35, 30, 25, 22],
                [38, 35, 30, 25, 21],
                [38, 35, 29, 28, 24],
                [38, 35, 29, 28, 23],
                [38, 35, 29, 28, 22],
                [38, 35, 29, 28, 21],
                [38, 35, 29, 27, 24],
                [38, 35, 29, 27, 23],
                [38, 35, 29, 27, 22],
                [38, 35, 29, 27, 21],
                [38, 35, 29, 26, 24],
                [38, 35, 29, 26, 23],
                [38, 35, 29, 26, 22],
                [38, 35, 29, 26, 21],
                [38, 35, 29, 25, 24],
                [38, 35, 29, 25, 23],
                [38, 35, 29, 25, 22],
                [38, 35, 29, 25, 21],
                [38, 34, 32, 28, 24],
                [38, 34, 32, 28, 23],
                [38, 34, 32, 28, 22],
                [38, 34, 32, 28, 21],
                [38, 34, 32, 27, 24],
                [38, 34, 32, 27, 23],
                [38, 34, 32, 27, 22],
                [38, 34, 32, 27, 21],
                [38, 34, 32, 26, 24],
                [38, 34, 32, 26, 23],
                [38, 34, 32, 26, 22],
                [38, 34, 32, 26, 21],
                [38, 34, 32, 25, 24],
                [38, 34, 32, 25, 23],
                [38, 34, 32, 25, 22],
                [38, 34, 32, 25, 21],
                [38, 34, 31, 28, 24],
                [38, 34, 31, 28, 23],
                [38, 34, 31, 28, 22],
                [38, 34, 31, 28, 21],
                [38, 34, 31, 27, 24],
                [38, 34, 31, 27, 23],
                [38, 34, 31, 27, 22],
                [38, 34, 31, 27, 21],
                [38, 34, 31, 26, 24],
                [38, 34, 31, 26, 23],
                [38, 34, 31, 26, 22],
                [38, 34, 31, 26, 21],
                [38, 34, 31, 25, 24],
                [38, 34, 31, 25, 23],
                [38, 34, 31, 25, 22],
                [38, 34, 31, 25, 21],
                [38, 34, 30, 28, 24],
                [38, 34, 30, 28, 23],
                [38, 34, 30, 28, 22],
                [38, 34, 30, 28, 21],
                [38, 34, 30, 27, 24],
                [38, 34, 30, 27, 23],
                [38, 34, 30, 27, 22],
                [38, 34, 30, 27, 21],
                [38, 34, 30, 26, 24],
                [38, 34, 30, 26, 23],
                [38, 34, 30, 26, 21],
                [38, 34, 30, 25, 24],
                [38, 34, 30, 25, 23],
                [38, 34, 30, 25, 22],
                [38, 34, 30, 25, 21],
                [38, 34, 29, 28, 24],
                [38, 34, 29, 28, 23],
                [38, 34, 29, 28, 22],
                [38, 34, 29, 28, 21],
                [38, 34, 29, 27, 24],
                [38, 34, 29, 27, 23],
                [38, 34, 29, 27, 22],
                [38, 34, 29, 27, 21],
                [38, 34, 29, 26, 24],
                [38, 34, 29, 26, 23],
                [38, 34, 29, 26, 22],
                [38, 34, 29, 26, 21],
                [38, 34, 29, 25, 24],
                [38, 34, 29, 25, 23],
                [38, 34, 29, 25, 22],
                [38, 34, 29, 25, 21],
                [38, 33, 32, 28, 24],
                [38, 33, 32, 28, 23],
                [38, 33, 32, 28, 22],
                [38, 33, 32, 28, 21],
                [38, 33, 32, 27, 24],
                [38, 33, 32, 27, 23],
                [38, 33, 32, 27, 22],
                [38, 33, 32, 27, 21],
                [38, 33, 32, 26, 24],
                [38, 33, 32, 26, 23],
                [38, 33, 32, 26, 22],
                [38, 33, 32, 26, 21],
                [38, 33, 32, 25, 24],
                [38, 33, 32, 25, 23],
                [38, 33, 32, 25, 22],
                [38, 33, 32, 25, 21],
                [38, 33, 31, 28, 24],
                [38, 33, 31, 28, 23],
                [38, 33, 31, 28, 22],
                [38, 33, 31, 28, 21],
                [38, 33, 31, 27, 24],
                [38, 33, 31, 27, 23],
                [38, 33, 31, 27, 22],
                [38, 33, 31, 27, 21],
                [38, 33, 31, 26, 24],
                [38, 33, 31, 26, 23],
                [38, 33, 31, 26, 22],
                [38, 33, 31, 26, 21],
                [38, 33, 31, 25, 24],
                [38, 33, 31, 25, 23],
                [38, 33, 31, 25, 22],
                [38, 33, 31, 25, 21],
                [38, 33, 30, 28, 24],
                [38, 33, 30, 28, 23],
                [38, 33, 30, 28, 22],
                [38, 33, 30, 28, 21],
                [38, 33, 30, 27, 24],
                [38, 33, 30, 27, 23],
                [38, 33, 30, 27, 22],
                [38, 33, 30, 27, 21],
                [38, 33, 30, 26, 24],
                [38, 33, 30, 26, 23],
                [38, 33, 30, 26, 22],
                [38, 33, 30, 26, 21],
                [38, 33, 30, 25, 24],
                [38, 33, 30, 25, 23],
                [38, 33, 30, 25, 22],
                [38, 33, 30, 25, 21],
                [38, 33, 29, 28, 24],
                [38, 33, 29, 28, 23],
                [38, 33, 29, 28, 22],
                [38, 33, 29, 28, 21],
                [38, 33, 29, 27, 24],
                [38, 33, 29, 27, 23],
                [38, 33, 29, 27, 22],
                [38, 33, 29, 27, 21],
                [38, 33, 29, 26, 24],
                [38, 33, 29, 26, 23],
                [38, 33, 29, 26, 22],
                [38, 33, 29, 26, 21],
                [38, 33, 29, 25, 24],
                [38, 33, 29, 25, 23],
                [38, 33, 29, 25, 22],
                [38, 33, 29, 25, 21],
                [37, 36, 32, 28, 24],
                [37, 36, 32, 28, 23],
                [37, 36, 32, 28, 22],
                [37, 36, 32, 28, 21],
                [37, 36, 32, 27, 24],
                [37, 36, 32, 27, 23],
                [37, 36, 32, 27, 22],
                [37, 36, 32, 27, 21],
                [37, 36, 32, 26, 24],
                [37, 36, 32, 26, 23],
                [37, 36, 32, 26, 22],
                [37, 36, 32, 26, 21],
                [37, 36, 32, 25, 24],
                [37, 36, 32, 25, 23],
                [37, 36, 32, 25, 22],
                [37, 36, 32, 25, 21],
                [37, 36, 31, 28, 24],
                [37, 36, 31, 28, 23],
                [37, 36, 31, 28, 22],
                [37, 36, 31, 28, 21],
                [37, 36, 31, 27, 24],
                [37, 36, 31, 27, 23],
                [37, 36, 31, 27, 22],
                [37, 36, 31, 27, 21],
                [37, 36, 31, 26, 24],
                [37, 36, 31, 26, 23],
                [37, 36, 31, 26, 22],
                [37, 36, 31, 26, 21],
                [37, 36, 31, 25, 24],
                [37, 36, 31, 25, 23],
                [37, 36, 31, 25, 22],
                [37, 36, 31, 25, 21],
                [37, 36, 30, 28, 24],
                [37, 36, 30, 28, 23],
                [37, 36, 30, 28, 22],
                [37, 36, 30, 28, 21],
                [37, 36, 30, 27, 24],
                [37, 36, 30, 27, 23],
                [37, 36, 30, 27, 22],
                [37, 36, 30, 27, 21],
                [37, 36, 30, 26, 24],
                [37, 36, 30, 26, 23],
                [37, 36, 30, 26, 22],
                [37, 36, 30, 26, 21],
                [37, 36, 30, 25, 24],
                [37, 36, 30, 25, 23],
                [37, 36, 30, 25, 22],
                [37, 36, 30, 25, 21],
                [37, 36, 29, 28, 24],
                [37, 36, 29, 28, 23],
                [37, 36, 29, 28, 22],
                [37, 36, 29, 28, 21],
                [37, 36, 29, 27, 24],
                [37, 36, 29, 27, 23],
                [37, 36, 29, 27, 22],
                [37, 36, 29, 27, 21],
                [37, 36, 29, 26, 24],
                [37, 36, 29, 26, 23],
                [37, 36, 29, 26, 22],
                [37, 36, 29, 26, 21],
                [37, 36, 29, 25, 24],
                [37, 36, 29, 25, 23],
                [37, 36, 29, 25, 22],
                [37, 36, 29, 25, 21],
                [37, 35, 32, 28, 24],
                [37, 35, 32, 28, 23],
                [37, 35, 32, 28, 22],
                [37, 35, 32, 28, 21],
                [37, 35, 32, 27, 24],
                [37, 35, 32, 27, 23],
                [37, 35, 32, 27, 22],
                [37, 35, 32, 27, 21],
                [37, 35, 32, 26, 24],
                [37, 35, 32, 26, 23],
                [37, 35, 32, 26, 22],
                [37, 35, 32, 26, 21],
                [37, 35, 32, 25, 24],
                [37, 35, 32, 25, 23],
                [37, 35, 32, 25, 22],
                [37, 35, 32, 25, 21],
                [37, 35, 31, 28, 24],
                [37, 35, 31, 28, 23],
                [37, 35, 31, 28, 22],
                [37, 35, 31, 28, 21],
                [37, 35, 31, 27, 24],
                [37, 35, 31, 27, 23],
                [37, 35, 31, 27, 22],
                [37, 35, 31, 27, 21],
                [37, 35, 31, 26, 24],
                [37, 35, 31, 26, 23],
                [37, 35, 31, 26, 22],
                [37, 35, 31, 26, 21],
                [37, 35, 31, 25, 24],
                [37, 35, 31, 25, 23],
                [37, 35, 31, 25, 22],
                [37, 35, 31, 25, 21],
                [37, 35, 30, 28, 24],
                [37, 35, 30, 28, 23],
                [37, 35, 30, 28, 22],
                [37, 35, 30, 28, 21],
                [37, 35, 30, 27, 24],
                [37, 35, 30, 27, 23],
                [37, 35, 30, 27, 22],
                [37, 35, 30, 27, 21],
                [37, 35, 30, 26, 24],
                [37, 35, 30, 26, 23],
                [37, 35, 30, 26, 22],
                [37, 35, 30, 26, 21],
                [37, 35, 30, 25, 24],
                [37, 35, 30, 25, 23],
                [37, 35, 30, 25, 22],
                [37, 35, 30, 25, 21],
                [37, 35, 29, 28, 24],
                [37, 35, 29, 28, 23],
                [37, 35, 29, 28, 22],
                [37, 35, 29, 28, 21],
                [37, 35, 29, 27, 24],
                [37, 35, 29, 27, 23],
                [37, 35, 29, 27, 22],
                [37, 35, 29, 27, 21],
                [37, 35, 29, 26, 24],
                [37, 35, 29, 26, 23],
                [37, 35, 29, 26, 22],
                [37, 35, 29, 26, 21],
                [37, 35, 29, 25, 24],
                [37, 35, 29, 25, 23],
                [37, 35, 29, 25, 22],
                [37, 35, 29, 25, 21],
                [37, 34, 32, 28, 24],
                [37, 34, 32, 28, 23],
                [37, 34, 32, 28, 22],
                [37, 34, 32, 28, 21],
                [37, 34, 32, 27, 24],
                [37, 34, 32, 27, 23],
                [37, 34, 32, 27, 22],
                [37, 34, 32, 27, 21],
                [37, 34, 32, 26, 24],
                [37, 34, 32, 26, 23],
                [37, 34, 32, 26, 22],
                [37, 34, 32, 26, 21],
                [37, 34, 32, 25, 24],
                [37, 34, 32, 25, 23],
                [37, 34, 32, 25, 22],
                [37, 34, 32, 25, 21],
                [37, 34, 31, 28, 24],
                [37, 34, 31, 28, 23],
                [37, 34, 31, 28, 22],
                [37, 34, 31, 28, 21],
                [37, 34, 31, 27, 24],
                [37, 34, 31, 27, 23],
                [37, 34, 31, 27, 22],
                [37, 34, 31, 27, 21],
                [37, 34, 31, 26, 24],
                [37, 34, 31, 26, 23],
                [37, 34, 31, 26, 22],
                [37, 34, 31, 26, 21],
                [37, 34, 31, 25, 24],
                [37, 34, 31, 25, 23],
                [37, 34, 31, 25, 22],
                [37, 34, 31, 25, 21],
                [37, 34, 30, 28, 24],
                [37, 34, 30, 28, 23],
                [37, 34, 30, 28, 22],
                [37, 34, 30, 28, 21],
                [37, 34, 30, 27, 24],
                [37, 34, 30, 27, 23],
                [37, 34, 30, 27, 22],
                [37, 34, 30, 27, 21],
                [37, 34, 30, 26, 24],
                [37, 34, 30, 26, 23],
                [37, 34, 30, 26, 22],
                [37, 34, 30, 26, 21],
                [37, 34, 30, 25, 24],
                [37, 34, 30, 25, 23],
                [37, 34, 30, 25, 22],
                [37, 34, 30, 25, 21],
                [37, 34, 29, 28, 24],
                [37, 34, 29, 28, 23],
                [37, 34, 29, 28, 22],
                [37, 34, 29, 28, 21],
                [37, 34, 29, 27, 24],
                [37, 34, 29, 27, 23],
                [37, 34, 29, 27, 22],
                [37, 34, 29, 27, 21],
                [37, 34, 29, 26, 24],
                [37, 34, 29, 26, 23],
                [37, 34, 29, 26, 22],
                [37, 34, 29, 26, 21],
                [37, 34, 29, 25, 24],
                [37, 34, 29, 25, 23],
                [37, 34, 29, 25, 22],
                [37, 34, 29, 25, 21],
                [37, 33, 32, 28, 24],
                [37, 33, 32, 28, 23],
                [37, 33, 32, 28, 22],
                [37, 33, 32, 28, 21],
                [37, 33, 32, 27, 24],
                [37, 33, 32, 27, 23],
                [37, 33, 32, 27, 22],
                [37, 33, 32, 27, 21],
                [37, 33, 32, 26, 24],
                [37, 33, 32, 26, 23],
                [37, 33, 32, 26, 22],
                [37, 33, 32, 26, 21],
                [37, 33, 32, 25, 24],
                [37, 33, 32, 25, 23],
                [37, 33, 32, 25, 22],
                [37, 33, 32, 25, 21],
                [37, 33, 31, 28, 24],
                [37, 33, 31, 28, 23],
                [37, 33, 31, 28, 22],
                [37, 33, 31, 28, 21],
                [37, 33, 31, 27, 24],
                [37, 33, 31, 27, 23],
                [37, 33, 31, 27, 22],
                [37, 33, 31, 27, 21],
                [37, 33, 31, 26, 24],
                [37, 33, 31, 26, 23],
                [37, 33, 31, 26, 22],
                [37, 33, 31, 26, 21],
                [37, 33, 31, 25, 24],
                [37, 33, 31, 25, 23],
                [37, 33, 31, 25, 22],
                [37, 33, 31, 25, 21],
                [37, 33, 30, 28, 24],
                [37, 33, 30, 28, 23],
                [37, 33, 30, 28, 22],
                [37, 33, 30, 28, 21],
                [37, 33, 30, 27, 24],
                [37, 33, 30, 27, 23],
                [37, 33, 30, 27, 22],
                [37, 33, 30, 27, 21],
                [37, 33, 30, 26, 24],
                [37, 33, 30, 26, 23],
                [37, 33, 30, 26, 22],
                [37, 33, 30, 26, 21],
                [37, 33, 30, 25, 24],
                [37, 33, 30, 25, 23],
                [37, 33, 30, 25, 22],
                [37, 33, 30, 25, 21],
                [37, 33, 29, 28, 24],
                [37, 33, 29, 28, 23],
                [37, 33, 29, 28, 22],
                [37, 33, 29, 28, 21],
                [37, 33, 29, 27, 24],
                [37, 33, 29, 27, 23],
                [37, 33, 29, 27, 22],
                [37, 33, 29, 27, 21],
                [37, 33, 29, 26, 24],
                [37, 33, 29, 26, 23],
                [37, 33, 29, 26, 22],
                [37, 33, 29, 26, 21],
                [37, 33, 29, 25, 24],
                [37, 33, 29, 25, 23],
                [37, 33, 29, 25, 22],
                [36, 32, 28, 24, 19],
                [36, 32, 28, 24, 18],
                [36, 32, 28, 24, 17],
                [36, 32, 28, 23, 20],
                [36, 32, 28, 23, 19],
                [36, 32, 28, 23, 18],
                [36, 32, 28, 23, 17],
                [36, 32, 28, 22, 20],
                [36, 32, 28, 22, 19],
                [36, 32, 28, 22, 18],
                [36, 32, 28, 22, 17],
                [36, 32, 28, 21, 20],
                [36, 32, 28, 21, 19],
                [36, 32, 28, 21, 18],
                [36, 32, 28, 21, 17],
                [36, 32, 27, 24, 20],
                [36, 32, 27, 24, 19],
                [36, 32, 27, 24, 18],
                [36, 32, 27, 24, 17],
                [36, 32, 27, 23, 20],
                [36, 32, 27, 23, 19],
                [36, 32, 27, 23, 18],
                [36, 32, 27, 23, 17],
                [36, 32, 27, 22, 20],
                [36, 32, 27, 22, 19],
                [36, 32, 27, 22, 18],
                [36, 32, 27, 22, 17],
                [36, 32, 27, 21, 20],
                [36, 32, 27, 21, 19],
                [36, 32, 27, 21, 18],
                [36, 32, 27, 21, 17],
                [36, 32, 26, 24, 20],
                [36, 32, 26, 24, 19],
                [36, 32, 26, 24, 18],
                [36, 32, 26, 24, 17],
                [36, 32, 26, 23, 20],
                [36, 32, 26, 23, 19],
                [36, 32, 26, 23, 18],
                [36, 32, 26, 23, 17],
                [36, 32, 26, 22, 20],
                [36, 32, 26, 22, 19],
                [36, 32, 26, 22, 18],
                [36, 32, 26, 22, 17],
                [36, 32, 26, 21, 20],
                [36, 32, 26, 21, 19],
                [36, 32, 26, 21, 18],
                [36, 32, 26, 21, 17],
                [36, 32, 25, 24, 20],
                [36, 32, 25, 24, 19],
                [36, 32, 25, 24, 18],
                [36, 32, 25, 24, 17],
                [36, 32, 25, 23, 20],
                [36, 32, 25, 23, 19],
                [36, 32, 25, 23, 18],
                [36, 32, 25, 23, 17],
                [36, 32, 25, 22, 20],
                [36, 32, 25, 22, 19],
                [36, 32, 25, 22, 18],
                [36, 32, 25, 22, 17],
                [36, 32, 25, 21, 20],
                [36, 32, 25, 21, 19],
                [36, 32, 25, 21, 18],
                [36, 32, 25, 21, 17],
                [36, 31, 28, 24, 20],
                [36, 31, 28, 24, 19],
                [36, 31, 28, 24, 18],
                [36, 31, 28, 24, 17],
                [36, 31, 28, 23, 20],
                [36, 31, 28, 23, 19],
                [36, 31, 28, 23, 18],
                [36, 31, 28, 23, 17],
                [36, 31, 28, 22, 20],
                [36, 31, 28, 22, 19],
                [36, 31, 28, 22, 18],
                [36, 31, 28, 22, 17],
                [36, 31, 28, 21, 20],
                [36, 31, 28, 21, 19],
                [36, 31, 28, 21, 18],
                [36, 31, 28, 21, 17],
                [36, 31, 27, 24, 20],
                [36, 31, 27, 24, 19],
                [36, 31, 27, 24, 18],
                [36, 31, 27, 24, 17],
                [36, 31, 27, 23, 20],
                [36, 31, 27, 23, 19],
                [36, 31, 27, 23, 18],
                [36, 31, 27, 23, 17],
                [36, 31, 27, 22, 20],
                [36, 31, 27, 22, 19],
                [36, 31, 27, 22, 18],
                [36, 31, 27, 22, 17],
                [36, 31, 27, 21, 20],
                [36, 31, 27, 21, 19],
                [36, 31, 27, 21, 18],
                [36, 31, 27, 21, 17],
                [36, 31, 26, 24, 20],
                [36, 31, 26, 24, 19],
                [36, 31, 26, 24, 18],
                [36, 31, 26, 24, 17],
                [36, 31, 26, 23, 20],
                [36, 31, 26, 23, 19],
                [36, 31, 26, 23, 18],
                [36, 31, 26, 23, 17],
                [36, 31, 26, 22, 20],
                [36, 31, 26, 22, 19],
                [36, 31, 26, 22, 18],
                [36, 31, 26, 22, 17],
                [36, 31, 26, 21, 20],
                [36, 31, 26, 21, 19],
                [36, 31, 26, 21, 18],
                [36, 31, 26, 21, 17],
                [36, 31, 25, 24, 20],
                [36, 31, 25, 24, 19],
                [36, 31, 25, 24, 18],
                [36, 31, 25, 24, 17],
                [36, 31, 25, 23, 20],
                [36, 31, 25, 23, 19],
                [36, 31, 25, 23, 18],
                [36, 31, 25, 23, 17],
                [36, 31, 25, 22, 20],
                [36, 31, 25, 22, 19],
                [36, 31, 25, 22, 18],
                [36, 31, 25, 22, 17],
                [36, 31, 25, 21, 20],
                [36, 31, 25, 21, 19],
                [36, 31, 25, 21, 18],
                [36, 31, 25, 21, 17],
                [36, 30, 28, 24, 20],
                [36, 30, 28, 24, 19],
                [36, 30, 28, 24, 18],
                [36, 30, 28, 24, 17],
                [36, 30, 28, 23, 20],
                [36, 30, 28, 23, 19],
                [36, 30, 28, 23, 18],
                [36, 30, 28, 23, 17],
                [36, 30, 28, 22, 20],
                [36, 30, 28, 22, 19],
                [36, 30, 28, 22, 18],
                [36, 30, 28, 22, 17],
                [36, 30, 28, 21, 20],
                [36, 30, 28, 21, 19],
                [36, 30, 28, 21, 18],
                [36, 30, 28, 21, 17],
                [36, 30, 27, 24, 20],
                [36, 30, 27, 24, 19],
                [36, 30, 27, 24, 18],
                [36, 30, 27, 24, 17],
                [36, 30, 27, 23, 20],
                [36, 30, 27, 23, 19],
                [36, 30, 27, 23, 18],
                [36, 30, 27, 23, 17],
                [36, 30, 27, 22, 20],
                [36, 30, 27, 22, 19],
                [36, 30, 27, 22, 18],
                [36, 30, 27, 22, 17],
                [36, 30, 27, 21, 20],
                [36, 30, 27, 21, 19],
                [36, 30, 27, 21, 18],
                [36, 30, 27, 21, 17],
                [36, 30, 26, 24, 20],
                [36, 30, 26, 24, 19],
                [36, 30, 26, 24, 18],
                [36, 30, 26, 24, 17],
                [36, 30, 26, 23, 20],
                [36, 30, 26, 23, 19],
                [36, 30, 26, 23, 18],
                [36, 30, 26, 23, 17],
                [36, 30, 26, 22, 20],
                [36, 30, 26, 22, 19],
                [36, 30, 26, 22, 18],
                [36, 30, 26, 22, 17],
                [36, 30, 26, 21, 20],
                [36, 30, 26, 21, 19],
                [36, 30, 26, 21, 18],
                [36, 30, 26, 21, 17],
                [36, 30, 25, 24, 20],
                [36, 30, 25, 24, 19],
                [36, 30, 25, 24, 18],
                [36, 30, 25, 24, 17],
                [36, 30, 25, 23, 20],
                [36, 30, 25, 23, 19],
                [36, 30, 25, 23, 18],
                [36, 30, 25, 23, 17],
                [36, 30, 25, 22, 20],
                [36, 30, 25, 22, 19],
                [36, 30, 25, 22, 18],
                [36, 30, 25, 22, 17],
                [36, 30, 25, 21, 20],
                [36, 30, 25, 21, 19],
                [36, 30, 25, 21, 18],
                [36, 30, 25, 21, 17],
                [36, 29, 28, 24, 20],
                [36, 29, 28, 24, 19],
                [36, 29, 28, 24, 18],
                [36, 29, 28, 24, 17],
                [36, 29, 28, 23, 20],
                [36, 29, 28, 23, 19],
                [36, 29, 28, 23, 18],
                [36, 29, 28, 23, 17],
                [36, 29, 28, 22, 20],
                [36, 29, 28, 22, 19],
                [36, 29, 28, 22, 18],
                [36, 29, 28, 22, 17],
                [36, 29, 28, 21, 20],
                [36, 29, 28, 21, 19],
                [36, 29, 28, 21, 18],
                [36, 29, 28, 21, 17],
                [36, 29, 27, 24, 20],
                [36, 29, 27, 24, 19],
                [36, 29, 27, 24, 18],
                [36, 29, 27, 24, 17],
                [36, 29, 27, 23, 20],
                [36, 29, 27, 23, 19],
                [36, 29, 27, 23, 18],
                [36, 29, 27, 23, 17],
                [36, 29, 27, 22, 20],
                [36, 29, 27, 22, 19],
                [36, 29, 27, 22, 18],
                [36, 29, 27, 22, 17],
                [36, 29, 27, 21, 20],
                [36, 29, 27, 21, 19],
                [36, 29, 27, 21, 18],
                [36, 29, 27, 21, 17],
                [36, 29, 26, 24, 20],
                [36, 29, 26, 24, 19],
                [36, 29, 26, 24, 18],
                [36, 29, 26, 24, 17],
                [36, 29, 26, 23, 20],
                [36, 29, 26, 23, 19],
                [36, 29, 26, 23, 18],
                [36, 29, 26, 23, 17],
                [36, 29, 26, 22, 20],
                [36, 29, 26, 22, 19],
                [36, 29, 26, 22, 18],
                [36, 29, 26, 22, 17],
                [36, 29, 26, 21, 20],
                [36, 29, 26, 21, 19],
                [36, 29, 26, 21, 18],
                [36, 29, 26, 21, 17],
                [36, 29, 25, 24, 20],
                [36, 29, 25, 24, 19],
                [36, 29, 25, 24, 18],
                [36, 29, 25, 24, 17],
                [36, 29, 25, 23, 20],
                [36, 29, 25, 23, 19],
                [36, 29, 25, 23, 18],
                [36, 29, 25, 23, 17],
                [36, 29, 25, 22, 20],
                [36, 29, 25, 22, 19],
                [36, 29, 25, 22, 18],
                [36, 29, 25, 22, 17],
                [36, 29, 25, 21, 20],
                [36, 29, 25, 21, 19],
                [36, 29, 25, 21, 18],
                [36, 29, 25, 21, 17],
                [35, 32, 28, 24, 20],
                [35, 32, 28, 24, 19],
                [35, 32, 28, 24, 18],
                [35, 32, 28, 24, 17],
                [35, 32, 28, 23, 20],
                [35, 32, 28, 23, 19],
                [35, 32, 28, 23, 18],
                [35, 32, 28, 23, 17],
                [35, 32, 28, 22, 20],
                [35, 32, 28, 22, 19],
                [35, 32, 28, 22, 18],
                [35, 32, 28, 22, 17],
                [35, 32, 28, 21, 20],
                [35, 32, 28, 21, 19],
                [35, 32, 28, 21, 18],
                [35, 32, 28, 21, 17],
                [35, 32, 27, 24, 20],
                [35, 32, 27, 24, 19],
                [35, 32, 27, 24, 18],
                [35, 32, 27, 24, 17],
                [35, 32, 27, 23, 20],
                [35, 32, 27, 23, 19],
                [35, 32, 27, 23, 18],
                [35, 32, 27, 23, 17],
                [35, 32, 27, 22, 20],
                [35, 32, 27, 22, 19],
                [35, 32, 27, 22, 18],
                [35, 32, 27, 22, 17],
                [35, 32, 27, 21, 20],
                [35, 32, 27, 21, 19],
                [35, 32, 27, 21, 18],
                [35, 32, 27, 21, 17],
                [35, 32, 26, 24, 20],
                [35, 32, 26, 24, 19],
                [35, 32, 26, 24, 18],
                [35, 32, 26, 24, 17],
                [35, 32, 26, 23, 20],
                [35, 32, 26, 23, 19],
                [35, 32, 26, 23, 18],
                [35, 32, 26, 23, 17],
                [35, 32, 26, 22, 20],
                [35, 32, 26, 22, 19],
                [35, 32, 26, 22, 18],
                [35, 32, 26, 22, 17],
                [35, 32, 26, 21, 20],
                [35, 32, 26, 21, 19],
                [35, 32, 26, 21, 18],
                [35, 32, 26, 21, 17],
                [35, 32, 25, 24, 20],
                [35, 32, 25, 24, 19],
                [35, 32, 25, 24, 18],
                [35, 32, 25, 24, 17],
                [35, 32, 25, 23, 20],
                [35, 32, 25, 23, 19],
                [35, 32, 25, 23, 18],
                [35, 32, 25, 23, 17],
                [35, 32, 25, 22, 20],
                [35, 32, 25, 22, 19],
                [35, 32, 25, 22, 18],
                [35, 32, 25, 22, 17],
                [35, 32, 25, 21, 20],
                [35, 32, 25, 21, 19],
                [35, 32, 25, 21, 18],
                [35, 32, 25, 21, 17],
                [35, 31, 28, 24, 20],
                [35, 31, 28, 24, 19],
                [35, 31, 28, 24, 18],
                [35, 31, 28, 24, 17],
                [35, 31, 28, 23, 20],
                [35, 31, 28, 23, 19],
                [35, 31, 28, 23, 18],
                [35, 31, 28, 23, 17],
                [35, 31, 28, 22, 20],
                [35, 31, 28, 22, 19],
                [35, 31, 28, 22, 18],
                [35, 31, 28, 22, 17],
                [35, 31, 28, 21, 20],
                [35, 31, 28, 21, 19],
                [35, 31, 28, 21, 18],
                [35, 31, 28, 21, 17],
                [35, 31, 27, 24, 20],
                [35, 31, 27, 24, 19],
                [35, 31, 27, 24, 18],
                [35, 31, 27, 24, 17],
                [35, 31, 27, 23, 20],
                [35, 31, 27, 23, 18],
                [35, 31, 27, 23, 17],
                [35, 31, 27, 22, 20],
                [35, 31, 27, 22, 19],
                [35, 31, 27, 22, 18],
                [35, 31, 27, 22, 17],
                [35, 31, 27, 21, 20],
                [35, 31, 27, 21, 19],
                [35, 31, 27, 21, 18],
                [35, 31, 27, 21, 17],
                [35, 31, 26, 24, 20],
                [35, 31, 26, 24, 19],
                [35, 31, 26, 24, 18],
                [35, 31, 26, 24, 17],
                [35, 31, 26, 23, 20],
                [35, 31, 26, 23, 19],
                [35, 31, 26, 23, 18],
                [35, 31, 26, 23, 17],
                [35, 31, 26, 22, 20],
                [35, 31, 26, 22, 19],
                [35, 31, 26, 22, 18],
                [35, 31, 26, 22, 17],
                [35, 31, 26, 21, 20],
                [35, 31, 26, 21, 19],
                [35, 31, 26, 21, 18],
                [35, 31, 26, 21, 17],
                [35, 31, 25, 24, 20],
                [35, 31, 25, 24, 19],
                [35, 31, 25, 24, 18],
                [35, 31, 25, 24, 17],
                [35, 31, 25, 23, 20],
                [35, 31, 25, 23, 19],
                [35, 31, 25, 23, 18],
                [35, 31, 25, 23, 17],
                [35, 31, 25, 22, 20],
                [35, 31, 25, 22, 19],
                [35, 31, 25, 22, 18],
                [35, 31, 25, 22, 17],
                [35, 31, 25, 21, 20],
                [35, 31, 25, 21, 19],
                [35, 31, 25, 21, 18],
                [35, 31, 25, 21, 17],
                [35, 30, 28, 24, 20],
                [35, 30, 28, 24, 19],
                [35, 30, 28, 24, 18],
                [35, 30, 28, 24, 17],
                [35, 30, 28, 23, 20],
                [35, 30, 28, 23, 19],
                [35, 30, 28, 23, 18],
                [35, 30, 28, 23, 17],
                [35, 30, 28, 22, 20],
                [35, 30, 28, 22, 19],
                [35, 30, 28, 22, 18],
                [35, 30, 28, 22, 17],
                [35, 30, 28, 21, 20],
                [35, 30, 28, 21, 19],
                [35, 30, 28, 21, 18],
                [35, 30, 28, 21, 17],
                [35, 30, 27, 24, 20],
                [35, 30, 27, 24, 19],
                [35, 30, 27, 24, 18],
                [35, 30, 27, 24, 17],
                [35, 30, 27, 23, 20],
                [35, 30, 27, 23, 19],
                [35, 30, 27, 23, 18],
                [35, 30, 27, 23, 17],
                [35, 30, 27, 22, 20],
                [35, 30, 27, 22, 19],
                [35, 30, 27, 22, 18],
                [35, 30, 27, 22, 17],
                [35, 30, 27, 21, 20],
                [35, 30, 27, 21, 19],
                [35, 30, 27, 21, 18],
                [35, 30, 27, 21, 17],
                [35, 30, 26, 24, 20],
                [35, 30, 26, 24, 19],
                [35, 30, 26, 24, 18],
                [35, 30, 26, 24, 17],
                [35, 30, 26, 23, 20],
                [35, 30, 26, 23, 19],
                [35, 30, 26, 23, 18],
                [35, 30, 26, 23, 17],
                [35, 30, 26, 22, 20],
                [35, 30, 26, 22, 19],
                [35, 30, 26, 22, 18],
                [35, 30, 26, 22, 17],
                [35, 30, 26, 21, 20],
                [35, 30, 26, 21, 19],
                [35, 30, 26, 21, 18],
                [35, 30, 26, 21, 17],
                [35, 30, 25, 24, 20],
                [35, 30, 25, 24, 19],
                [35, 30, 25, 24, 18],
                [35, 30, 25, 24, 17],
                [35, 30, 25, 23, 20],
                [35, 30, 25, 23, 19],
                [35, 30, 25, 23, 18],
                [35, 30, 25, 23, 17],
                [35, 30, 25, 22, 20],
                [35, 30, 25, 22, 19],
                [35, 30, 25, 22, 18],
                [35, 30, 25, 22, 17],
                [35, 30, 25, 21, 20],
                [35, 30, 25, 21, 19],
                [35, 30, 25, 21, 18],
                [35, 30, 25, 21, 17],
                [35, 29, 28, 24, 20],
                [35, 29, 28, 24, 19],
                [35, 29, 28, 24, 18],
                [35, 29, 28, 24, 17],
                [35, 29, 28, 23, 20],
                [35, 29, 28, 23, 19],
                [35, 29, 28, 23, 18],
                [35, 29, 28, 23, 17],
                [35, 29, 28, 22, 20],
                [35, 29, 28, 22, 19],
                [35, 29, 28, 22, 18],
                [35, 29, 28, 22, 17],
                [35, 29, 28, 21, 20],
                [35, 29, 28, 21, 19],
                [35, 29, 28, 21, 18],
                [35, 29, 28, 21, 17],
                [35, 29, 27, 24, 20],
                [35, 29, 27, 24, 19],
                [35, 29, 27, 24, 18],
                [35, 29, 27, 24, 17],
                [35, 29, 27, 23, 20],
                [35, 29, 27, 23, 19],
                [35, 29, 27, 23, 18],
                [35, 29, 27, 23, 17],
                [35, 29, 27, 22, 20],
                [35, 29, 27, 22, 19],
                [35, 29, 27, 22, 18],
                [35, 29, 27, 22, 17],
                [35, 29, 27, 21, 20],
                [35, 29, 27, 21, 19],
                [35, 29, 27, 21, 18],
                [35, 29, 27, 21, 17],
                [35, 29, 26, 24, 20],
                [35, 29, 26, 24, 19],
                [35, 29, 26, 24, 18],
                [35, 29, 26, 24, 17],
                [35, 29, 26, 23, 20],
                [35, 29, 26, 23, 19],
                [35, 29, 26, 23, 18],
                [35, 29, 26, 23, 17],
                [35, 29, 26, 22, 20],
                [35, 29, 26, 22, 19],
                [35, 29, 26, 22, 18],
                [35, 29, 26, 22, 17],
                [35, 29, 26, 21, 20],
                [35, 29, 26, 21, 19],
                [35, 29, 26, 21, 18],
                [35, 29, 26, 21, 17],
                [35, 29, 25, 24, 20],
                [35, 29, 25, 24, 19],
                [35, 29, 25, 24, 18],
                [35, 29, 25, 24, 17],
                [35, 29, 25, 23, 20],
                [35, 29, 25, 23, 19],
                [35, 29, 25, 23, 18],
                [35, 29, 25, 23, 17],
                [35, 29, 25, 22, 20],
                [35, 29, 25, 22, 19],
                [35, 29, 25, 22, 18],
                [35, 29, 25, 22, 17],
                [35, 29, 25, 21, 20],
                [35, 29, 25, 21, 19],
                [35, 29, 25, 21, 18],
                [35, 29, 25, 21, 17],
                [34, 32, 28, 24, 20],
                [34, 32, 28, 24, 19],
                [34, 32, 28, 24, 18],
                [34, 32, 28, 24, 17],
                [34, 32, 28, 23, 20],
                [34, 32, 28, 23, 19],
                [34, 32, 28, 23, 18],
                [34, 32, 28, 23, 17],
                [34, 32, 28, 22, 20],
                [34, 32, 28, 22, 19],
                [34, 32, 28, 22, 18],
                [34, 32, 28, 22, 17],
                [34, 32, 28, 21, 20],
                [34, 32, 28, 21, 19],
                [34, 32, 28, 21, 18],
                [34, 32, 28, 21, 17],
                [34, 32, 27, 24, 20],
                [34, 32, 27, 24, 19],
                [34, 32, 27, 24, 18],
                [34, 32, 27, 24, 17],
                [34, 32, 27, 23, 20],
                [34, 32, 27, 23, 19],
                [34, 32, 27, 23, 18],
                [34, 32, 27, 23, 17],
                [34, 32, 27, 22, 20],
                [34, 32, 27, 22, 19],
                [34, 32, 27, 22, 18],
                [34, 32, 27, 22, 17],
                [34, 32, 27, 21, 20],
                [34, 32, 27, 21, 19],
                [34, 32, 27, 21, 18],
                [34, 32, 27, 21, 17],
                [34, 32, 26, 24, 20],
                [34, 32, 26, 24, 19],
                [34, 32, 26, 24, 18],
                [34, 32, 26, 24, 17],
                [34, 32, 26, 23, 20],
                [34, 32, 26, 23, 19],
                [34, 32, 26, 23, 18],
                [34, 32, 26, 23, 17],
                [34, 32, 26, 22, 20],
                [34, 32, 26, 22, 19],
                [34, 32, 26, 22, 18],
                [34, 32, 26, 22, 17],
                [34, 32, 26, 21, 20],
                [34, 32, 26, 21, 19],
                [34, 32, 26, 21, 18],
                [34, 32, 26, 21, 17],
                [34, 32, 25, 24, 20],
                [34, 32, 25, 24, 19],
                [34, 32, 25, 24, 18],
                [34, 32, 25, 24, 17],
                [34, 32, 25, 23, 20],
                [34, 32, 25, 23, 19],
                [34, 32, 25, 23, 18],
                [34, 32, 25, 23, 17],
                [34, 32, 25, 22, 20],
                [34, 32, 25, 22, 19],
                [34, 32, 25, 22, 18],
                [34, 32, 25, 22, 17],
                [34, 32, 25, 21, 20],
                [34, 32, 25, 21, 19],
                [34, 32, 25, 21, 18],
                [34, 32, 25, 21, 17],
                [34, 31, 28, 24, 20],
                [34, 31, 28, 24, 19],
                [34, 31, 28, 24, 18],
                [34, 31, 28, 24, 17],
                [34, 31, 28, 23, 20],
                [34, 31, 28, 23, 19],
                [34, 31, 28, 23, 18],
                [34, 31, 28, 23, 17],
                [34, 31, 28, 22, 20],
                [34, 31, 28, 22, 19],
                [34, 31, 28, 22, 18],
                [34, 31, 28, 22, 17],
                [34, 31, 28, 21, 20],
                [34, 31, 28, 21, 19],
                [34, 31, 28, 21, 18],
                [34, 31, 28, 21, 17],
                [34, 31, 27, 24, 20],
                [34, 31, 27, 24, 19],
                [34, 31, 27, 24, 18],
                [34, 31, 27, 24, 17],
                [34, 31, 27, 23, 20],
                [34, 31, 27, 23, 19],
                [34, 31, 27, 23, 18],
                [34, 31, 27, 23, 17],
                [34, 31, 27, 22, 20],
                [34, 31, 27, 22, 19],
                [34, 31, 27, 22, 18],
                [34, 31, 27, 22, 17],
                [34, 31, 27, 21, 20],
                [34, 31, 27, 21, 19],
                [34, 31, 27, 21, 18],
                [34, 31, 27, 21, 17],
                [34, 31, 26, 24, 20],
                [34, 31, 26, 24, 19],
                [34, 31, 26, 24, 18],
                [34, 31, 26, 24, 17],
                [34, 31, 26, 23, 20],
                [34, 31, 26, 23, 19],
                [34, 31, 26, 23, 18],
                [34, 31, 26, 23, 17],
                [34, 31, 26, 22, 20],
                [34, 31, 26, 22, 19],
                [34, 31, 26, 22, 18],
                [34, 31, 26, 22, 17],
                [34, 31, 26, 21, 20],
                [34, 31, 26, 21, 19],
                [34, 31, 26, 21, 18],
                [34, 31, 26, 21, 17],
                [34, 31, 25, 24, 20],
                [34, 31, 25, 24, 19],
                [34, 31, 25, 24, 18],
                [34, 31, 25, 24, 17],
                [34, 31, 25, 23, 20],
                [34, 31, 25, 23, 19],
                [34, 31, 25, 23, 18],
                [34, 31, 25, 23, 17],
                [34, 31, 25, 22, 20],
                [34, 31, 25, 22, 19],
                [34, 31, 25, 22, 18],
                [34, 31, 25, 22, 17],
                [34, 31, 25, 21, 20],
                [34, 31, 25, 21, 19],
                [34, 31, 25, 21, 18],
                [34, 31, 25, 21, 17],
                [34, 30, 28, 24, 20],
                [34, 30, 28, 24, 19],
                [34, 30, 28, 24, 18],
                [34, 30, 28, 24, 17],
                [34, 30, 28, 23, 20],
                [34, 30, 28, 23, 19],
                [34, 30, 28, 23, 18],
                [34, 30, 28, 23, 17],
                [34, 30, 28, 22, 20],
                [34, 30, 28, 22, 19],
                [34, 30, 28, 22, 18],
                [34, 30, 28, 22, 17],
                [34, 30, 28, 21, 20],
                [34, 30, 28, 21, 19],
                [34, 30, 28, 21, 18],
                [34, 30, 28, 21, 17],
                [34, 30, 27, 24, 20],
                [34, 30, 27, 24, 19],
                [34, 30, 27, 24, 18],
                [34, 30, 27, 24, 17],
                [34, 30, 27, 23, 20],
                [34, 30, 27, 23, 19],
                [34, 30, 27, 23, 18],
                [34, 30, 27, 23, 17],
                [34, 30, 27, 22, 20],
                [34, 30, 27, 22, 19],
                [34, 30, 27, 22, 18],
                [34, 30, 27, 22, 17],
                [34, 30, 27, 21, 20],
                [34, 30, 27, 21, 19],
                [34, 30, 27, 21, 18],
                [34, 30, 27, 21, 17],
                [34, 30, 26, 24, 20],
                [34, 30, 26, 24, 19],
                [34, 30, 26, 24, 18],
                [34, 30, 26, 24, 17],
                [34, 30, 26, 23, 20],
                [34, 30, 26, 23, 19],
                [34, 30, 26, 23, 18],
                [34, 30, 26, 23, 17],
                [34, 30, 26, 22, 20],
                [34, 30, 26, 22, 19],
                [34, 30, 26, 22, 17],
                [34, 30, 26, 21, 20],
                [34, 30, 26, 21, 19],
                [34, 30, 26, 21, 18],
                [34, 30, 26, 21, 17],
                [34, 30, 25, 24, 20],
                [34, 30, 25, 24, 19],
                [34, 30, 25, 24, 18],
                [34, 30, 25, 24, 17],
                [34, 30, 25, 23, 20],
                [34, 30, 25, 23, 19],
                [34, 30, 25, 23, 18],
                [34, 30, 25, 23, 17],
                [34, 30, 25, 22, 20],
                [34, 30, 25, 22, 19],
                [34, 30, 25, 22, 18],
                [34, 30, 25, 22, 17],
                [34, 30, 25, 21, 20],
                [34, 30, 25, 21, 19],
                [34, 30, 25, 21, 18],
                [34, 30, 25, 21, 17],
                [34, 29, 28, 24, 20],
                [34, 29, 28, 24, 19],
                [34, 29, 28, 24, 18],
                [34, 29, 28, 24, 17],
                [34, 29, 28, 23, 20],
                [34, 29, 28, 23, 19],
                [34, 29, 28, 23, 18],
                [34, 29, 28, 23, 17],
                [34, 29, 28, 22, 20],
                [34, 29, 28, 22, 19],
                [34, 29, 28, 22, 18],
                [34, 29, 28, 22, 17],
                [34, 29, 28, 21, 20],
                [34, 29, 28, 21, 19],
                [34, 29, 28, 21, 18],
                [34, 29, 28, 21, 17],
                [34, 29, 27, 24, 20],
                [34, 29, 27, 24, 19],
                [34, 29, 27, 24, 18],
                [34, 29, 27, 24, 17],
                [34, 29, 27, 23, 20],
                [34, 29, 27, 23, 19],
                [34, 29, 27, 23, 18],
                [34, 29, 27, 23, 17],
                [34, 29, 27, 22, 20],
                [34, 29, 27, 22, 19],
                [34, 29, 27, 22, 18],
                [34, 29, 27, 22, 17],
                [34, 29, 27, 21, 20],
                [34, 29, 27, 21, 19],
                [34, 29, 27, 21, 18],
                [34, 29, 27, 21, 17],
                [34, 29, 26, 24, 20],
                [34, 29, 26, 24, 19],
                [34, 29, 26, 24, 18],
                [34, 29, 26, 24, 17],
                [34, 29, 26, 23, 20],
                [34, 29, 26, 23, 19],
                [34, 29, 26, 23, 18],
                [34, 29, 26, 23, 17],
                [34, 29, 26, 22, 20],
                [34, 29, 26, 22, 19],
                [34, 29, 26, 22, 18],
                [34, 29, 26, 22, 17],
                [34, 29, 26, 21, 20],
                [34, 29, 26, 21, 19],
                [34, 29, 26, 21, 18],
                [34, 29, 26, 21, 17],
                [34, 29, 25, 24, 20],
                [34, 29, 25, 24, 19],
                [34, 29, 25, 24, 18],
                [34, 29, 25, 24, 17],
                [34, 29, 25, 23, 20],
                [34, 29, 25, 23, 19],
                [34, 29, 25, 23, 18],
                [34, 29, 25, 23, 17],
                [34, 29, 25, 22, 20],
                [34, 29, 25, 22, 19],
                [34, 29, 25, 22, 18],
                [34, 29, 25, 22, 17],
                [34, 29, 25, 21, 20],
                [34, 29, 25, 21, 19],
                [34, 29, 25, 21, 18],
                [34, 29, 25, 21, 17],
                [33, 32, 28, 24, 20],
                [33, 32, 28, 24, 19],
                [33, 32, 28, 24, 18],
                [33, 32, 28, 24, 17],
                [33, 32, 28, 23, 20],
                [33, 32, 28, 23, 19],
                [33, 32, 28, 23, 18],
                [33, 32, 28, 23, 17],
                [33, 32, 28, 22, 20],
                [33, 32, 28, 22, 19],
                [33, 32, 28, 22, 18],
                [33, 32, 28, 22, 17],
                [33, 32, 28, 21, 20],
                [33, 32, 28, 21, 19],
                [33, 32, 28, 21, 18],
                [33, 32, 28, 21, 17],
                [33, 32, 27, 24, 20],
                [33, 32, 27, 24, 19],
                [33, 32, 27, 24, 18],
                [33, 32, 27, 24, 17],
                [33, 32, 27, 23, 20],
                [33, 32, 27, 23, 19],
                [33, 32, 27, 23, 18],
                [33, 32, 27, 23, 17],
                [33, 32, 27, 22, 20],
                [33, 32, 27, 22, 19],
                [33, 32, 27, 22, 18],
                [33, 32, 27, 22, 17],
                [33, 32, 27, 21, 20],
                [33, 32, 27, 21, 19],
                [33, 32, 27, 21, 18],
                [33, 32, 27, 21, 17],
                [33, 32, 26, 24, 20],
                [33, 32, 26, 24, 19],
                [33, 32, 26, 24, 18],
                [33, 32, 26, 24, 17],
                [33, 32, 26, 23, 20],
                [33, 32, 26, 23, 19],
                [33, 32, 26, 23, 18],
                [33, 32, 26, 23, 17],
                [33, 32, 26, 22, 20],
                [33, 32, 26, 22, 19],
                [33, 32, 26, 22, 18],
                [33, 32, 26, 22, 17],
                [33, 32, 26, 21, 20],
                [33, 32, 26, 21, 19],
                [33, 32, 26, 21, 18],
                [33, 32, 26, 21, 17],
                [33, 32, 25, 24, 20],
                [33, 32, 25, 24, 19],
                [33, 32, 25, 24, 18],
                [33, 32, 25, 24, 17],
                [33, 32, 25, 23, 20],
                [33, 32, 25, 23, 19],
                [33, 32, 25, 23, 18],
                [33, 32, 25, 23, 17],
                [33, 32, 25, 22, 20],
                [33, 32, 25, 22, 19],
                [33, 32, 25, 22, 18],
                [33, 32, 25, 22, 17],
                [33, 32, 25, 21, 20],
                [33, 32, 25, 21, 19],
                [33, 32, 25, 21, 18],
                [33, 32, 25, 21, 17],
                [33, 31, 28, 24, 20],
                [33, 31, 28, 24, 19],
                [33, 31, 28, 24, 18],
                [33, 31, 28, 24, 17],
                [33, 31, 28, 23, 20],
                [33, 31, 28, 23, 19],
                [33, 31, 28, 23, 18],
                [33, 31, 28, 23, 17],
                [33, 31, 28, 22, 20],
                [33, 31, 28, 22, 19],
                [33, 31, 28, 22, 18],
                [33, 31, 28, 22, 17],
                [33, 31, 28, 21, 20],
                [33, 31, 28, 21, 19],
                [33, 31, 28, 21, 18],
                [33, 31, 28, 21, 17],
                [33, 31, 27, 24, 20],
                [33, 31, 27, 24, 19],
                [33, 31, 27, 24, 18],
                [33, 31, 27, 24, 17],
                [33, 31, 27, 23, 20],
                [33, 31, 27, 23, 19],
                [33, 31, 27, 23, 18],
                [33, 31, 27, 23, 17],
                [33, 31, 27, 22, 20],
                [33, 31, 27, 22, 19],
                [33, 31, 27, 22, 18],
                [33, 31, 27, 22, 17],
                [33, 31, 27, 21, 20],
                [33, 31, 27, 21, 19],
                [33, 31, 27, 21, 18],
                [33, 31, 27, 21, 17],
                [33, 31, 26, 24, 20],
                [33, 31, 26, 24, 19],
                [33, 31, 26, 24, 18],
                [33, 31, 26, 24, 17],
                [33, 31, 26, 23, 20],
                [33, 31, 26, 23, 19],
                [33, 31, 26, 23, 18],
                [33, 31, 26, 23, 17],
                [33, 31, 26, 22, 20],
                [33, 31, 26, 22, 19],
                [33, 31, 26, 22, 18],
                [33, 31, 26, 22, 17],
                [33, 31, 26, 21, 20],
                [33, 31, 26, 21, 19],
                [33, 31, 26, 21, 18],
                [33, 31, 26, 21, 17],
                [33, 31, 25, 24, 20],
                [33, 31, 25, 24, 19],
                [33, 31, 25, 24, 18],
                [33, 31, 25, 24, 17],
                [33, 31, 25, 23, 20],
                [33, 31, 25, 23, 19],
                [33, 31, 25, 23, 18],
                [33, 31, 25, 23, 17],
                [33, 31, 25, 22, 20],
                [33, 31, 25, 22, 19],
                [33, 31, 25, 22, 18],
                [33, 31, 25, 22, 17],
                [33, 31, 25, 21, 20],
                [33, 31, 25, 21, 19],
                [33, 31, 25, 21, 18],
                [33, 31, 25, 21, 17],
                [33, 30, 28, 24, 20],
                [33, 30, 28, 24, 19],
                [33, 30, 28, 24, 18],
                [33, 30, 28, 24, 17],
                [33, 30, 28, 23, 20],
                [33, 30, 28, 23, 19],
                [33, 30, 28, 23, 18],
                [33, 30, 28, 23, 17],
                [33, 30, 28, 22, 20],
                [33, 30, 28, 22, 19],
                [33, 30, 28, 22, 18],
                [33, 30, 28, 22, 17],
                [33, 30, 28, 21, 20],
                [33, 30, 28, 21, 19],
                [33, 30, 28, 21, 18],
                [33, 30, 28, 21, 17],
                [33, 30, 27, 24, 20],
                [33, 30, 27, 24, 19],
                [33, 30, 27, 24, 18],
                [33, 30, 27, 24, 17],
                [33, 30, 27, 23, 20],
                [33, 30, 27, 23, 19],
                [33, 30, 27, 23, 18],
                [33, 30, 27, 23, 17],
                [33, 30, 27, 22, 20],
                [33, 30, 27, 22, 19],
                [33, 30, 27, 22, 18],
                [33, 30, 27, 22, 17],
                [33, 30, 27, 21, 20],
                [33, 30, 27, 21, 19],
                [33, 30, 27, 21, 18],
                [33, 30, 27, 21, 17],
                [33, 30, 26, 24, 20],
                [33, 30, 26, 24, 19],
                [33, 30, 26, 24, 18],
                [33, 30, 26, 24, 17],
                [33, 30, 26, 23, 20],
                [33, 30, 26, 23, 19],
                [33, 30, 26, 23, 18],
                [33, 30, 26, 23, 17],
                [33, 30, 26, 22, 20],
                [33, 30, 26, 22, 19],
                [33, 30, 26, 22, 18],
                [33, 30, 26, 22, 17],
                [33, 30, 26, 21, 20],
                [33, 30, 26, 21, 19],
                [33, 30, 26, 21, 18],
                [33, 30, 26, 21, 17],
                [33, 30, 25, 24, 20],
                [33, 30, 25, 24, 19],
                [33, 30, 25, 24, 18],
                [33, 30, 25, 24, 17],
                [33, 30, 25, 23, 20],
                [33, 30, 25, 23, 19],
                [33, 30, 25, 23, 18],
                [33, 30, 25, 23, 17],
                [33, 30, 25, 22, 20],
                [33, 30, 25, 22, 19],
                [33, 30, 25, 22, 18],
                [33, 30, 25, 22, 17],
                [33, 30, 25, 21, 20],
                [33, 30, 25, 21, 19],
                [33, 30, 25, 21, 18],
                [33, 30, 25, 21, 17],
                [33, 29, 28, 24, 20],
                [33, 29, 28, 24, 19],
                [33, 29, 28, 24, 18],
                [33, 29, 28, 24, 17],
                [33, 29, 28, 23, 20],
                [33, 29, 28, 23, 19],
                [33, 29, 28, 23, 18],
                [33, 29, 28, 23, 17],
                [33, 29, 28, 22, 20],
                [33, 29, 28, 22, 19],
                [33, 29, 28, 22, 18],
                [33, 29, 28, 22, 17],
                [33, 29, 28, 21, 20],
                [33, 29, 28, 21, 19],
                [33, 29, 28, 21, 18],
                [33, 29, 28, 21, 17],
                [33, 29, 27, 24, 20],
                [33, 29, 27, 24, 19],
                [33, 29, 27, 24, 18],
                [33, 29, 27, 24, 17],
                [33, 29, 27, 23, 20],
                [33, 29, 27, 23, 19],
                [33, 29, 27, 23, 18],
                [33, 29, 27, 23, 17],
                [33, 29, 27, 22, 20],
                [33, 29, 27, 22, 19],
                [33, 29, 27, 22, 18],
                [33, 29, 27, 22, 17],
                [33, 29, 27, 21, 20],
                [33, 29, 27, 21, 19],
                [33, 29, 27, 21, 18],
                [33, 29, 27, 21, 17],
                [33, 29, 26, 24, 20],
                [33, 29, 26, 24, 19],
                [33, 29, 26, 24, 18],
                [33, 29, 26, 24, 17],
                [33, 29, 26, 23, 20],
                [33, 29, 26, 23, 19],
                [33, 29, 26, 23, 18],
                [33, 29, 26, 23, 17],
                [33, 29, 26, 22, 20],
                [33, 29, 26, 22, 19],
                [33, 29, 26, 22, 18],
                [33, 29, 26, 22, 17],
                [33, 29, 26, 21, 20],
                [33, 29, 26, 21, 19],
                [33, 29, 26, 21, 18],
                [33, 29, 26, 21, 17],
                [33, 29, 25, 24, 20],
                [33, 29, 25, 24, 19],
                [33, 29, 25, 24, 18],
                [33, 29, 25, 24, 17],
                [33, 29, 25, 23, 20],
                [33, 29, 25, 23, 19],
                [33, 29, 25, 23, 18],
                [33, 29, 25, 23, 17],
                [33, 29, 25, 22, 20],
                [33, 29, 25, 22, 19],
                [33, 29, 25, 22, 18],
                [33, 29, 25, 22, 17],
                [33, 29, 25, 21, 20],
                [33, 29, 25, 21, 19],
                [33, 29, 25, 21, 18],
                [32, 28, 24, 20, 15],
                [32, 28, 24, 20, 14],
                [32, 28, 24, 20, 13],
                [32, 28, 24, 19, 16],
                [32, 28, 24, 19, 15],
                [32, 28, 24, 19, 14],
                [32, 28, 24, 19, 13],
                [32, 28, 24, 18, 16],
                [32, 28, 24, 18, 15],
                [32, 28, 24, 18, 14],
                [32, 28, 24, 18, 13],
                [32, 28, 24, 17, 16],
                [32, 28, 24, 17, 15],
                [32, 28, 24, 17, 14],
                [32, 28, 24, 17, 13],
                [32, 28, 23, 20, 16],
                [32, 28, 23, 20, 15],
                [32, 28, 23, 20, 14],
                [32, 28, 23, 20, 13],
                [32, 28, 23, 19, 16],
                [32, 28, 23, 19, 15],
                [32, 28, 23, 19, 14],
                [32, 28, 23, 19, 13],
                [32, 28, 23, 18, 16],
                [32, 28, 23, 18, 15],
                [32, 28, 23, 18, 14],
                [32, 28, 23, 18, 13],
                [32, 28, 23, 17, 16],
                [32, 28, 23, 17, 15],
                [32, 28, 23, 17, 14],
                [32, 28, 23, 17, 13],
                [32, 28, 22, 20, 16],
                [32, 28, 22, 20, 15],
                [32, 28, 22, 20, 14],
                [32, 28, 22, 20, 13],
                [32, 28, 22, 19, 16],
                [32, 28, 22, 19, 15],
                [32, 28, 22, 19, 14],
                [32, 28, 22, 19, 13],
                [32, 28, 22, 18, 16],
                [32, 28, 22, 18, 15],
                [32, 28, 22, 18, 14],
                [32, 28, 22, 18, 13],
                [32, 28, 22, 17, 16],
                [32, 28, 22, 17, 15],
                [32, 28, 22, 17, 14],
                [32, 28, 22, 17, 13],
                [32, 28, 21, 20, 16],
                [32, 28, 21, 20, 15],
                [32, 28, 21, 20, 14],
                [32, 28, 21, 20, 13],
                [32, 28, 21, 19, 16],
                [32, 28, 21, 19, 15],
                [32, 28, 21, 19, 14],
                [32, 28, 21, 19, 13],
                [32, 28, 21, 18, 16],
                [32, 28, 21, 18, 15],
                [32, 28, 21, 18, 14],
                [32, 28, 21, 18, 13],
                [32, 28, 21, 17, 16],
                [32, 28, 21, 17, 15],
                [32, 28, 21, 17, 14],
                [32, 28, 21, 17, 13],
                [32, 27, 24, 20, 16],
                [32, 27, 24, 20, 15],
                [32, 27, 24, 20, 14],
                [32, 27, 24, 20, 13],
                [32, 27, 24, 19, 16],
                [32, 27, 24, 19, 15],
                [32, 27, 24, 19, 14],
                [32, 27, 24, 19, 13],
                [32, 27, 24, 18, 16],
                [32, 27, 24, 18, 15],
                [32, 27, 24, 18, 14],
                [32, 27, 24, 18, 13],
                [32, 27, 24, 17, 16],
                [32, 27, 24, 17, 15],
                [32, 27, 24, 17, 14],
                [32, 27, 24, 17, 13],
                [32, 27, 23, 20, 16],
                [32, 27, 23, 20, 15],
                [32, 27, 23, 20, 14],
                [32, 27, 23, 20, 13],
                [32, 27, 23, 19, 16],
                [32, 27, 23, 19, 15],
                [32, 27, 23, 19, 14],
                [32, 27, 23, 19, 13],
                [32, 27, 23, 18, 16],
                [32, 27, 23, 18, 15],
                [32, 27, 23, 18, 14],
                [32, 27, 23, 18, 13],
                [32, 27, 23, 17, 16],
                [32, 27, 23, 17, 15],
                [32, 27, 23, 17, 14],
                [32, 27, 23, 17, 13],
                [32, 27, 22, 20, 16],
                [32, 27, 22, 20, 15],
                [32, 27, 22, 20, 14],
                [32, 27, 22, 20, 13],
                [32, 27, 22, 19, 16],
                [32, 27, 22, 19, 15],
                [32, 27, 22, 19, 14],
                [32, 27, 22, 19, 13],
                [32, 27, 22, 18, 16],
                [32, 27, 22, 18, 15],
                [32, 27, 22, 18, 14],
                [32, 27, 22, 18, 13],
                [32, 27, 22, 17, 16],
                [32, 27, 22, 17, 15],
                [32, 27, 22, 17, 14],
                [32, 27, 22, 17, 13],
                [32, 27, 21, 20, 16],
                [32, 27, 21, 20, 15],
                [32, 27, 21, 20, 14],
                [32, 27, 21, 20, 13],
                [32, 27, 21, 19, 16],
                [32, 27, 21, 19, 15],
                [32, 27, 21, 19, 14],
                [32, 27, 21, 19, 13],
                [32, 27, 21, 18, 16],
                [32, 27, 21, 18, 15],
                [32, 27, 21, 18, 14],
                [32, 27, 21, 18, 13],
                [32, 27, 21, 17, 16],
                [32, 27, 21, 17, 15],
                [32, 27, 21, 17, 14],
                [32, 27, 21, 17, 13],
                [32, 26, 24, 20, 16],
                [32, 26, 24, 20, 15],
                [32, 26, 24, 20, 14],
                [32, 26, 24, 20, 13],
                [32, 26, 24, 19, 16],
                [32, 26, 24, 19, 15],
                [32, 26, 24, 19, 14],
                [32, 26, 24, 19, 13],
                [32, 26, 24, 18, 16],
                [32, 26, 24, 18, 15],
                [32, 26, 24, 18, 14],
                [32, 26, 24, 18, 13],
                [32, 26, 24, 17, 16],
                [32, 26, 24, 17, 15],
                [32, 26, 24, 17, 14],
                [32, 26, 24, 17, 13],
                [32, 26, 23, 20, 16],
                [32, 26, 23, 20, 15],
                [32, 26, 23, 20, 14],
                [32, 26, 23, 20, 13],
                [32, 26, 23, 19, 16],
                [32, 26, 23, 19, 15],
                [32, 26, 23, 19, 14],
                [32, 26, 23, 19, 13],
                [32, 26, 23, 18, 16],
                [32, 26, 23, 18, 15],
                [32, 26, 23, 18, 14],
                [32, 26, 23, 18, 13],
                [32, 26, 23, 17, 16],
                [32, 26, 23, 17, 15],
                [32, 26, 23, 17, 14],
                [32, 26, 23, 17, 13],
                [32, 26, 22, 20, 16],
                [32, 26, 22, 20, 15],
                [32, 26, 22, 20, 14],
                [32, 26, 22, 20, 13],
                [32, 26, 22, 19, 16],
                [32, 26, 22, 19, 15],
                [32, 26, 22, 19, 14],
                [32, 26, 22, 19, 13],
                [32, 26, 22, 18, 16],
                [32, 26, 22, 18, 15],
                [32, 26, 22, 18, 14],
                [32, 26, 22, 18, 13],
                [32, 26, 22, 17, 16],
                [32, 26, 22, 17, 15],
                [32, 26, 22, 17, 14],
                [32, 26, 22, 17, 13],
                [32, 26, 21, 20, 16],
                [32, 26, 21, 20, 15],
                [32, 26, 21, 20, 14],
                [32, 26, 21, 20, 13],
                [32, 26, 21, 19, 16],
                [32, 26, 21, 19, 15],
                [32, 26, 21, 19, 14],
                [32, 26, 21, 19, 13],
                [32, 26, 21, 18, 16],
                [32, 26, 21, 18, 15],
                [32, 26, 21, 18, 14],
                [32, 26, 21, 18, 13],
                [32, 26, 21, 17, 16],
                [32, 26, 21, 17, 15],
                [32, 26, 21, 17, 14],
                [32, 26, 21, 17, 13],
                [32, 25, 24, 20, 16],
                [32, 25, 24, 20, 15],
                [32, 25, 24, 20, 14],
                [32, 25, 24, 20, 13],
                [32, 25, 24, 19, 16],
                [32, 25, 24, 19, 15],
                [32, 25, 24, 19, 14],
                [32, 25, 24, 19, 13],
                [32, 25, 24, 18, 16],
                [32, 25, 24, 18, 15],
                [32, 25, 24, 18, 14],
                [32, 25, 24, 18, 13],
                [32, 25, 24, 17, 16],
                [32, 25, 24, 17, 15],
                [32, 25, 24, 17, 14],
                [32, 25, 24, 17, 13],
                [32, 25, 23, 20, 16],
                [32, 25, 23, 20, 15],
                [32, 25, 23, 20, 14],
                [32, 25, 23, 20, 13],
                [32, 25, 23, 19, 16],
                [32, 25, 23, 19, 15],
                [32, 25, 23, 19, 14],
                [32, 25, 23, 19, 13],
                [32, 25, 23, 18, 16],
                [32, 25, 23, 18, 15],
                [32, 25, 23, 18, 14],
                [32, 25, 23, 18, 13],
                [32, 25, 23, 17, 16],
                [32, 25, 23, 17, 15],
                [32, 25, 23, 17, 14],
                [32, 25, 23, 17, 13],
                [32, 25, 22, 20, 16],
                [32, 25, 22, 20, 15],
                [32, 25, 22, 20, 14],
                [32, 25, 22, 20, 13],
                [32, 25, 22, 19, 16],
                [32, 25, 22, 19, 15],
                [32, 25, 22, 19, 14],
                [32, 25, 22, 19, 13],
                [32, 25, 22, 18, 16],
                [32, 25, 22, 18, 15],
                [32, 25, 22, 18, 14],
                [32, 25, 22, 18, 13],
                [32, 25, 22, 17, 16],
                [32, 25, 22, 17, 15],
                [32, 25, 22, 17, 14],
                [32, 25, 22, 17, 13],
                [32, 25, 21, 20, 16],
                [32, 25, 21, 20, 15],
                [32, 25, 21, 20, 14],
                [32, 25, 21, 20, 13],
                [32, 25, 21, 19, 16],
                [32, 25, 21, 19, 15],
                [32, 25, 21, 19, 14],
                [32, 25, 21, 19, 13],
                [32, 25, 21, 18, 16],
                [32, 25, 21, 18, 15],
                [32, 25, 21, 18, 14],
                [32, 25, 21, 18, 13],
                [32, 25, 21, 17, 16],
                [32, 25, 21, 17, 15],
                [32, 25, 21, 17, 14],
                [32, 25, 21, 17, 13],
                [31, 28, 24, 20, 16],
                [31, 28, 24, 20, 15],
                [31, 28, 24, 20, 14],
                [31, 28, 24, 20, 13],
                [31, 28, 24, 19, 16],
                [31, 28, 24, 19, 15],
                [31, 28, 24, 19, 14],
                [31, 28, 24, 19, 13],
                [31, 28, 24, 18, 16],
                [31, 28, 24, 18, 15],
                [31, 28, 24, 18, 14],
                [31, 28, 24, 18, 13],
                [31, 28, 24, 17, 16],
                [31, 28, 24, 17, 15],
                [31, 28, 24, 17, 14],
                [31, 28, 24, 17, 13],
                [31, 28, 23, 20, 16],
                [31, 28, 23, 20, 15],
                [31, 28, 23, 20, 14],
                [31, 28, 23, 20, 13],
                [31, 28, 23, 19, 16],
                [31, 28, 23, 19, 15],
                [31, 28, 23, 19, 14],
                [31, 28, 23, 19, 13],
                [31, 28, 23, 18, 16],
                [31, 28, 23, 18, 15],
                [31, 28, 23, 18, 14],
                [31, 28, 23, 18, 13],
                [31, 28, 23, 17, 16],
                [31, 28, 23, 17, 15],
                [31, 28, 23, 17, 14],
                [31, 28, 23, 17, 13],
                [31, 28, 22, 20, 16],
                [31, 28, 22, 20, 15],
                [31, 28, 22, 20, 14],
                [31, 28, 22, 20, 13],
                [31, 28, 22, 19, 16],
                [31, 28, 22, 19, 15],
                [31, 28, 22, 19, 14],
                [31, 28, 22, 19, 13],
                [31, 28, 22, 18, 16],
                [31, 28, 22, 18, 15],
                [31, 28, 22, 18, 14],
                [31, 28, 22, 18, 13],
                [31, 28, 22, 17, 16],
                [31, 28, 22, 17, 15],
                [31, 28, 22, 17, 14],
                [31, 28, 22, 17, 13],
                [31, 28, 21, 20, 16],
                [31, 28, 21, 20, 15],
                [31, 28, 21, 20, 14],
                [31, 28, 21, 20, 13],
                [31, 28, 21, 19, 16],
                [31, 28, 21, 19, 15],
                [31, 28, 21, 19, 14],
                [31, 28, 21, 19, 13],
                [31, 28, 21, 18, 16],
                [31, 28, 21, 18, 15],
                [31, 28, 21, 18, 14],
                [31, 28, 21, 18, 13],
                [31, 28, 21, 17, 16],
                [31, 28, 21, 17, 15],
                [31, 28, 21, 17, 14],
                [31, 28, 21, 17, 13],
                [31, 27, 24, 20, 16],
                [31, 27, 24, 20, 15],
                [31, 27, 24, 20, 14],
                [31, 27, 24, 20, 13],
                [31, 27, 24, 19, 16],
                [31, 27, 24, 19, 15],
                [31, 27, 24, 19, 14],
                [31, 27, 24, 19, 13],
                [31, 27, 24, 18, 16],
                [31, 27, 24, 18, 15],
                [31, 27, 24, 18, 14],
                [31, 27, 24, 18, 13],
                [31, 27, 24, 17, 16],
                [31, 27, 24, 17, 15],
                [31, 27, 24, 17, 14],
                [31, 27, 24, 17, 13],
                [31, 27, 23, 20, 16],
                [31, 27, 23, 20, 15],
                [31, 27, 23, 20, 14],
                [31, 27, 23, 20, 13],
                [31, 27, 23, 19, 16],
                [31, 27, 23, 19, 14],
                [31, 27, 23, 19, 13],
                [31, 27, 23, 18, 16],
                [31, 27, 23, 18, 15],
                [31, 27, 23, 18, 14],
                [31, 27, 23, 18, 13],
                [31, 27, 23, 17, 16],
                [31, 27, 23, 17, 15],
                [31, 27, 23, 17, 14],
                [31, 27, 23, 17, 13],
                [31, 27, 22, 20, 16],
                [31, 27, 22, 20, 15],
                [31, 27, 22, 20, 14],
                [31, 27, 22, 20, 13],
                [31, 27, 22, 19, 16],
                [31, 27, 22, 19, 15],
                [31, 27, 22, 19, 14],
                [31, 27, 22, 19, 13],
                [31, 27, 22, 18, 16],
                [31, 27, 22, 18, 15],
                [31, 27, 22, 18, 14],
                [31, 27, 22, 18, 13],
                [31, 27, 22, 17, 16],
                [31, 27, 22, 17, 15],
                [31, 27, 22, 17, 14],
                [31, 27, 22, 17, 13],
                [31, 27, 21, 20, 16],
                [31, 27, 21, 20, 15],
                [31, 27, 21, 20, 14],
                [31, 27, 21, 20, 13],
                [31, 27, 21, 19, 16],
                [31, 27, 21, 19, 15],
                [31, 27, 21, 19, 14],
                [31, 27, 21, 19, 13],
                [31, 27, 21, 18, 16],
                [31, 27, 21, 18, 15],
                [31, 27, 21, 18, 14],
                [31, 27, 21, 18, 13],
                [31, 27, 21, 17, 16],
                [31, 27, 21, 17, 15],
                [31, 27, 21, 17, 14],
                [31, 27, 21, 17, 13],
                [31, 26, 24, 20, 16],
                [31, 26, 24, 20, 15],
                [31, 26, 24, 20, 14],
                [31, 26, 24, 20, 13],
                [31, 26, 24, 19, 16],
                [31, 26, 24, 19, 15],
                [31, 26, 24, 19, 14],
                [31, 26, 24, 19, 13],
                [31, 26, 24, 18, 16],
                [31, 26, 24, 18, 15],
                [31, 26, 24, 18, 14],
                [31, 26, 24, 18, 13],
                [31, 26, 24, 17, 16],
                [31, 26, 24, 17, 15],
                [31, 26, 24, 17, 14],
                [31, 26, 24, 17, 13],
                [31, 26, 23, 20, 16],
                [31, 26, 23, 20, 15],
                [31, 26, 23, 20, 14],
                [31, 26, 23, 20, 13],
                [31, 26, 23, 19, 16],
                [31, 26, 23, 19, 15],
                [31, 26, 23, 19, 14],
                [31, 26, 23, 19, 13],
                [31, 26, 23, 18, 16],
                [31, 26, 23, 18, 15],
                [31, 26, 23, 18, 14],
                [31, 26, 23, 18, 13],
                [31, 26, 23, 17, 16],
                [31, 26, 23, 17, 15],
                [31, 26, 23, 17, 14],
                [31, 26, 23, 17, 13],
                [31, 26, 22, 20, 16],
                [31, 26, 22, 20, 15],
                [31, 26, 22, 20, 14],
                [31, 26, 22, 20, 13],
                [31, 26, 22, 19, 16],
                [31, 26, 22, 19, 15],
                [31, 26, 22, 19, 14],
                [31, 26, 22, 19, 13],
                [31, 26, 22, 18, 16],
                [31, 26, 22, 18, 15],
                [31, 26, 22, 18, 14],
                [31, 26, 22, 18, 13],
                [31, 26, 22, 17, 16],
                [31, 26, 22, 17, 15],
                [31, 26, 22, 17, 14],
                [31, 26, 22, 17, 13],
                [31, 26, 21, 20, 16],
                [31, 26, 21, 20, 15],
                [31, 26, 21, 20, 14],
                [31, 26, 21, 20, 13],
                [31, 26, 21, 19, 16],
                [31, 26, 21, 19, 15],
                [31, 26, 21, 19, 14],
                [31, 26, 21, 19, 13],
                [31, 26, 21, 18, 16],
                [31, 26, 21, 18, 15],
                [31, 26, 21, 18, 14],
                [31, 26, 21, 18, 13],
                [31, 26, 21, 17, 16],
                [31, 26, 21, 17, 15],
                [31, 26, 21, 17, 14],
                [31, 26, 21, 17, 13],
                [31, 25, 24, 20, 16],
                [31, 25, 24, 20, 15],
                [31, 25, 24, 20, 14],
                [31, 25, 24, 20, 13],
                [31, 25, 24, 19, 16],
                [31, 25, 24, 19, 15],
                [31, 25, 24, 19, 14],
                [31, 25, 24, 19, 13],
                [31, 25, 24, 18, 16],
                [31, 25, 24, 18, 15],
                [31, 25, 24, 18, 14],
                [31, 25, 24, 18, 13],
                [31, 25, 24, 17, 16],
                [31, 25, 24, 17, 15],
                [31, 25, 24, 17, 14],
                [31, 25, 24, 17, 13],
                [31, 25, 23, 20, 16],
                [31, 25, 23, 20, 15],
                [31, 25, 23, 20, 14],
                [31, 25, 23, 20, 13],
                [31, 25, 23, 19, 16],
                [31, 25, 23, 19, 15],
                [31, 25, 23, 19, 14],
                [31, 25, 23, 19, 13],
                [31, 25, 23, 18, 16],
                [31, 25, 23, 18, 15],
                [31, 25, 23, 18, 14],
                [31, 25, 23, 18, 13],
                [31, 25, 23, 17, 16],
                [31, 25, 23, 17, 15],
                [31, 25, 23, 17, 14],
                [31, 25, 23, 17, 13],
                [31, 25, 22, 20, 16],
                [31, 25, 22, 20, 15],
                [31, 25, 22, 20, 14],
                [31, 25, 22, 20, 13],
                [31, 25, 22, 19, 16],
                [31, 25, 22, 19, 15],
                [31, 25, 22, 19, 14],
                [31, 25, 22, 19, 13],
                [31, 25, 22, 18, 16],
                [31, 25, 22, 18, 15],
                [31, 25, 22, 18, 14],
                [31, 25, 22, 18, 13],
                [31, 25, 22, 17, 16],
                [31, 25, 22, 17, 15],
                [31, 25, 22, 17, 14],
                [31, 25, 22, 17, 13],
                [31, 25, 21, 20, 16],
                [31, 25, 21, 20, 15],
                [31, 25, 21, 20, 14],
                [31, 25, 21, 20, 13],
                [31, 25, 21, 19, 16],
                [31, 25, 21, 19, 15],
                [31, 25, 21, 19, 14],
                [31, 25, 21, 19, 13],
                [31, 25, 21, 18, 16],
                [31, 25, 21, 18, 15],
                [31, 25, 21, 18, 14],
                [31, 25, 21, 18, 13],
                [31, 25, 21, 17, 16],
                [31, 25, 21, 17, 15],
                [31, 25, 21, 17, 14],
                [31, 25, 21, 17, 13],
                [30, 28, 24, 20, 16],
                [30, 28, 24, 20, 15],
                [30, 28, 24, 20, 14],
                [30, 28, 24, 20, 13],
                [30, 28, 24, 19, 16],
                [30, 28, 24, 19, 15],
                [30, 28, 24, 19, 14],
                [30, 28, 24, 19, 13],
                [30, 28, 24, 18, 16],
                [30, 28, 24, 18, 15],
                [30, 28, 24, 18, 14],
                [30, 28, 24, 18, 13],
                [30, 28, 24, 17, 16],
                [30, 28, 24, 17, 15],
                [30, 28, 24, 17, 14],
                [30, 28, 24, 17, 13],
                [30, 28, 23, 20, 16],
                [30, 28, 23, 20, 15],
                [30, 28, 23, 20, 14],
                [30, 28, 23, 20, 13],
                [30, 28, 23, 19, 16],
                [30, 28, 23, 19, 15],
                [30, 28, 23, 19, 14],
                [30, 28, 23, 19, 13],
                [30, 28, 23, 18, 16],
                [30, 28, 23, 18, 15],
                [30, 28, 23, 18, 14],
                [30, 28, 23, 18, 13],
                [30, 28, 23, 17, 16],
                [30, 28, 23, 17, 15],
                [30, 28, 23, 17, 14],
                [30, 28, 23, 17, 13],
                [30, 28, 22, 20, 16],
                [30, 28, 22, 20, 15],
                [30, 28, 22, 20, 14],
                [30, 28, 22, 20, 13],
                [30, 28, 22, 19, 16],
                [30, 28, 22, 19, 15],
                [30, 28, 22, 19, 14],
                [30, 28, 22, 19, 13],
                [30, 28, 22, 18, 16],
                [30, 28, 22, 18, 15],
                [30, 28, 22, 18, 14],
                [30, 28, 22, 18, 13],
                [30, 28, 22, 17, 16],
                [30, 28, 22, 17, 15],
                [30, 28, 22, 17, 14],
                [30, 28, 22, 17, 13],
                [30, 28, 21, 20, 16],
                [30, 28, 21, 20, 15],
                [30, 28, 21, 20, 14],
                [30, 28, 21, 20, 13],
                [30, 28, 21, 19, 16],
                [30, 28, 21, 19, 15],
                [30, 28, 21, 19, 14],
                [30, 28, 21, 19, 13],
                [30, 28, 21, 18, 16],
                [30, 28, 21, 18, 15],
                [30, 28, 21, 18, 14],
                [30, 28, 21, 18, 13],
                [30, 28, 21, 17, 16],
                [30, 28, 21, 17, 15],
                [30, 28, 21, 17, 14],
                [30, 28, 21, 17, 13],
                [30, 27, 24, 20, 16],
                [30, 27, 24, 20, 15],
                [30, 27, 24, 20, 14],
                [30, 27, 24, 20, 13],
                [30, 27, 24, 19, 16],
                [30, 27, 24, 19, 15],
                [30, 27, 24, 19, 14],
                [30, 27, 24, 19, 13],
                [30, 27, 24, 18, 16],
                [30, 27, 24, 18, 15],
                [30, 27, 24, 18, 14],
                [30, 27, 24, 18, 13],
                [30, 27, 24, 17, 16],
                [30, 27, 24, 17, 15],
                [30, 27, 24, 17, 14],
                [30, 27, 24, 17, 13],
                [30, 27, 23, 20, 16],
                [30, 27, 23, 20, 15],
                [30, 27, 23, 20, 14],
                [30, 27, 23, 20, 13],
                [30, 27, 23, 19, 16],
                [30, 27, 23, 19, 15],
                [30, 27, 23, 19, 14],
                [30, 27, 23, 19, 13],
                [30, 27, 23, 18, 16],
                [30, 27, 23, 18, 15],
                [30, 27, 23, 18, 14],
                [30, 27, 23, 18, 13],
                [30, 27, 23, 17, 16],
                [30, 27, 23, 17, 15],
                [30, 27, 23, 17, 14],
                [30, 27, 23, 17, 13],
                [30, 27, 22, 20, 16],
                [30, 27, 22, 20, 15],
                [30, 27, 22, 20, 14],
                [30, 27, 22, 20, 13],
                [30, 27, 22, 19, 16],
                [30, 27, 22, 19, 15],
                [30, 27, 22, 19, 14],
                [30, 27, 22, 19, 13],
                [30, 27, 22, 18, 16],
                [30, 27, 22, 18, 15],
                [30, 27, 22, 18, 14],
                [30, 27, 22, 18, 13],
                [30, 27, 22, 17, 16],
                [30, 27, 22, 17, 15],
                [30, 27, 22, 17, 14],
                [30, 27, 22, 17, 13],
                [30, 27, 21, 20, 16],
                [30, 27, 21, 20, 15],
                [30, 27, 21, 20, 14],
                [30, 27, 21, 20, 13],
                [30, 27, 21, 19, 16],
                [30, 27, 21, 19, 15],
                [30, 27, 21, 19, 14],
                [30, 27, 21, 19, 13],
                [30, 27, 21, 18, 16],
                [30, 27, 21, 18, 15],
                [30, 27, 21, 18, 14],
                [30, 27, 21, 18, 13],
                [30, 27, 21, 17, 16],
                [30, 27, 21, 17, 15],
                [30, 27, 21, 17, 14],
                [30, 27, 21, 17, 13],
                [30, 26, 24, 20, 16],
                [30, 26, 24, 20, 15],
                [30, 26, 24, 20, 14],
                [30, 26, 24, 20, 13],
                [30, 26, 24, 19, 16],
                [30, 26, 24, 19, 15],
                [30, 26, 24, 19, 14],
                [30, 26, 24, 19, 13],
                [30, 26, 24, 18, 16],
                [30, 26, 24, 18, 15],
                [30, 26, 24, 18, 14],
                [30, 26, 24, 18, 13],
                [30, 26, 24, 17, 16],
                [30, 26, 24, 17, 15],
                [30, 26, 24, 17, 14],
                [30, 26, 24, 17, 13],
                [30, 26, 23, 20, 16],
                [30, 26, 23, 20, 15],
                [30, 26, 23, 20, 14],
                [30, 26, 23, 20, 13],
                [30, 26, 23, 19, 16],
                [30, 26, 23, 19, 15],
                [30, 26, 23, 19, 14],
                [30, 26, 23, 19, 13],
                [30, 26, 23, 18, 16],
                [30, 26, 23, 18, 15],
                [30, 26, 23, 18, 14],
                [30, 26, 23, 18, 13],
                [30, 26, 23, 17, 16],
                [30, 26, 23, 17, 15],
                [30, 26, 23, 17, 14],
                [30, 26, 23, 17, 13],
                [30, 26, 22, 20, 16],
                [30, 26, 22, 20, 15],
                [30, 26, 22, 20, 14],
                [30, 26, 22, 20, 13],
                [30, 26, 22, 19, 16],
                [30, 26, 22, 19, 15],
                [30, 26, 22, 19, 14],
                [30, 26, 22, 19, 13],
                [30, 26, 22, 18, 16],
                [30, 26, 22, 18, 15],
                [30, 26, 22, 18, 13],
                [30, 26, 22, 17, 16],
                [30, 26, 22, 17, 15],
                [30, 26, 22, 17, 14],
                [30, 26, 22, 17, 13],
                [30, 26, 21, 20, 16],
                [30, 26, 21, 20, 15],
                [30, 26, 21, 20, 14],
                [30, 26, 21, 20, 13],
                [30, 26, 21, 19, 16],
                [30, 26, 21, 19, 15],
                [30, 26, 21, 19, 14],
                [30, 26, 21, 19, 13],
                [30, 26, 21, 18, 16],
                [30, 26, 21, 18, 15],
                [30, 26, 21, 18, 14],
                [30, 26, 21, 18, 13],
                [30, 26, 21, 17, 16],
                [30, 26, 21, 17, 15],
                [30, 26, 21, 17, 14],
                [30, 26, 21, 17, 13],
                [30, 25, 24, 20, 16],
                [30, 25, 24, 20, 15],
                [30, 25, 24, 20, 14],
                [30, 25, 24, 20, 13],
                [30, 25, 24, 19, 16],
                [30, 25, 24, 19, 15],
                [30, 25, 24, 19, 14],
                [30, 25, 24, 19, 13],
                [30, 25, 24, 18, 16],
                [30, 25, 24, 18, 15],
                [30, 25, 24, 18, 14],
                [30, 25, 24, 18, 13],
                [30, 25, 24, 17, 16],
                [30, 25, 24, 17, 15],
                [30, 25, 24, 17, 14],
                [30, 25, 24, 17, 13],
                [30, 25, 23, 20, 16],
                [30, 25, 23, 20, 15],
                [30, 25, 23, 20, 14],
                [30, 25, 23, 20, 13],
                [30, 25, 23, 19, 16],
                [30, 25, 23, 19, 15],
                [30, 25, 23, 19, 14],
                [30, 25, 23, 19, 13],
                [30, 25, 23, 18, 16],
                [30, 25, 23, 18, 15],
                [30, 25, 23, 18, 14],
                [30, 25, 23, 18, 13],
                [30, 25, 23, 17, 16],
                [30, 25, 23, 17, 15],
                [30, 25, 23, 17, 14],
                [30, 25, 23, 17, 13],
                [30, 25, 22, 20, 16],
                [30, 25, 22, 20, 15],
                [30, 25, 22, 20, 14],
                [30, 25, 22, 20, 13],
                [30, 25, 22, 19, 16],
                [30, 25, 22, 19, 15],
                [30, 25, 22, 19, 14],
                [30, 25, 22, 19, 13],
                [30, 25, 22, 18, 16],
                [30, 25, 22, 18, 15],
                [30, 25, 22, 18, 14],
                [30, 25, 22, 18, 13],
                [30, 25, 22, 17, 16],
                [30, 25, 22, 17, 15],
                [30, 25, 22, 17, 14],
                [30, 25, 22, 17, 13],
                [30, 25, 21, 20, 16],
                [30, 25, 21, 20, 15],
                [30, 25, 21, 20, 14],
                [30, 25, 21, 20, 13],
                [30, 25, 21, 19, 16],
                [30, 25, 21, 19, 15],
                [30, 25, 21, 19, 14],
                [30, 25, 21, 19, 13],
                [30, 25, 21, 18, 16],
                [30, 25, 21, 18, 15],
                [30, 25, 21, 18, 14],
                [30, 25, 21, 18, 13],
                [30, 25, 21, 17, 16],
                [30, 25, 21, 17, 15],
                [30, 25, 21, 17, 14],
                [30, 25, 21, 17, 13],
                [29, 28, 24, 20, 16],
                [29, 28, 24, 20, 15],
                [29, 28, 24, 20, 14],
                [29, 28, 24, 20, 13],
                [29, 28, 24, 19, 16],
                [29, 28, 24, 19, 15],
                [29, 28, 24, 19, 14],
                [29, 28, 24, 19, 13],
                [29, 28, 24, 18, 16],
                [29, 28, 24, 18, 15],
                [29, 28, 24, 18, 14],
                [29, 28, 24, 18, 13],
                [29, 28, 24, 17, 16],
                [29, 28, 24, 17, 15],
                [29, 28, 24, 17, 14],
                [29, 28, 24, 17, 13],
                [29, 28, 23, 20, 16],
                [29, 28, 23, 20, 15],
                [29, 28, 23, 20, 14],
                [29, 28, 23, 20, 13],
                [29, 28, 23, 19, 16],
                [29, 28, 23, 19, 15],
                [29, 28, 23, 19, 14],
                [29, 28, 23, 19, 13],
                [29, 28, 23, 18, 16],
                [29, 28, 23, 18, 15],
                [29, 28, 23, 18, 14],
                [29, 28, 23, 18, 13],
                [29, 28, 23, 17, 16],
                [29, 28, 23, 17, 15],
                [29, 28, 23, 17, 14],
                [29, 28, 23, 17, 13],
                [29, 28, 22, 20, 16],
                [29, 28, 22, 20, 15],
                [29, 28, 22, 20, 14],
                [29, 28, 22, 20, 13],
                [29, 28, 22, 19, 16],
                [29, 28, 22, 19, 15],
                [29, 28, 22, 19, 14],
                [29, 28, 22, 19, 13],
                [29, 28, 22, 18, 16],
                [29, 28, 22, 18, 15],
                [29, 28, 22, 18, 14],
                [29, 28, 22, 18, 13],
                [29, 28, 22, 17, 16],
                [29, 28, 22, 17, 15],
                [29, 28, 22, 17, 14],
                [29, 28, 22, 17, 13],
                [29, 28, 21, 20, 16],
                [29, 28, 21, 20, 15],
                [29, 28, 21, 20, 14],
                [29, 28, 21, 20, 13],
                [29, 28, 21, 19, 16],
                [29, 28, 21, 19, 15],
                [29, 28, 21, 19, 14],
                [29, 28, 21, 19, 13],
                [29, 28, 21, 18, 16],
                [29, 28, 21, 18, 15],
                [29, 28, 21, 18, 14],
                [29, 28, 21, 18, 13],
                [29, 28, 21, 17, 16],
                [29, 28, 21, 17, 15],
                [29, 28, 21, 17, 14],
                [29, 28, 21, 17, 13],
                [29, 27, 24, 20, 16],
                [29, 27, 24, 20, 15],
                [29, 27, 24, 20, 14],
                [29, 27, 24, 20, 13],
                [29, 27, 24, 19, 16],
                [29, 27, 24, 19, 15],
                [29, 27, 24, 19, 14],
                [29, 27, 24, 19, 13],
                [29, 27, 24, 18, 16],
                [29, 27, 24, 18, 15],
                [29, 27, 24, 18, 14],
                [29, 27, 24, 18, 13],
                [29, 27, 24, 17, 16],
                [29, 27, 24, 17, 15],
                [29, 27, 24, 17, 14],
                [29, 27, 24, 17, 13],
                [29, 27, 23, 20, 16],
                [29, 27, 23, 20, 15],
                [29, 27, 23, 20, 14],
                [29, 27, 23, 20, 13],
                [29, 27, 23, 19, 16],
                [29, 27, 23, 19, 15],
                [29, 27, 23, 19, 14],
                [29, 27, 23, 19, 13],
                [29, 27, 23, 18, 16],
                [29, 27, 23, 18, 15],
                [29, 27, 23, 18, 14],
                [29, 27, 23, 18, 13],
                [29, 27, 23, 17, 16],
                [29, 27, 23, 17, 15],
                [29, 27, 23, 17, 14],
                [29, 27, 23, 17, 13],
                [29, 27, 22, 20, 16],
                [29, 27, 22, 20, 15],
                [29, 27, 22, 20, 14],
                [29, 27, 22, 20, 13],
                [29, 27, 22, 19, 16],
                [29, 27, 22, 19, 15],
                [29, 27, 22, 19, 14],
                [29, 27, 22, 19, 13],
                [29, 27, 22, 18, 16],
                [29, 27, 22, 18, 15],
                [29, 27, 22, 18, 14],
                [29, 27, 22, 18, 13],
                [29, 27, 22, 17, 16],
                [29, 27, 22, 17, 15],
                [29, 27, 22, 17, 14],
                [29, 27, 22, 17, 13],
                [29, 27, 21, 20, 16],
                [29, 27, 21, 20, 15],
                [29, 27, 21, 20, 14],
                [29, 27, 21, 20, 13],
                [29, 27, 21, 19, 16],
                [29, 27, 21, 19, 15],
                [29, 27, 21, 19, 14],
                [29, 27, 21, 19, 13],
                [29, 27, 21, 18, 16],
                [29, 27, 21, 18, 15],
                [29, 27, 21, 18, 14],
                [29, 27, 21, 18, 13],
                [29, 27, 21, 17, 16],
                [29, 27, 21, 17, 15],
                [29, 27, 21, 17, 14],
                [29, 27, 21, 17, 13],
                [29, 26, 24, 20, 16],
                [29, 26, 24, 20, 15],
                [29, 26, 24, 20, 14],
                [29, 26, 24, 20, 13],
                [29, 26, 24, 19, 16],
                [29, 26, 24, 19, 15],
                [29, 26, 24, 19, 14],
                [29, 26, 24, 19, 13],
                [29, 26, 24, 18, 16],
                [29, 26, 24, 18, 15],
                [29, 26, 24, 18, 14],
                [29, 26, 24, 18, 13],
                [29, 26, 24, 17, 16],
                [29, 26, 24, 17, 15],
                [29, 26, 24, 17, 14],
                [29, 26, 24, 17, 13],
                [29, 26, 23, 20, 16],
                [29, 26, 23, 20, 15],
                [29, 26, 23, 20, 14],
                [29, 26, 23, 20, 13],
                [29, 26, 23, 19, 16],
                [29, 26, 23, 19, 15],
                [29, 26, 23, 19, 14],
                [29, 26, 23, 19, 13],
                [29, 26, 23, 18, 16],
                [29, 26, 23, 18, 15],
                [29, 26, 23, 18, 14],
                [29, 26, 23, 18, 13],
                [29, 26, 23, 17, 16],
                [29, 26, 23, 17, 15],
                [29, 26, 23, 17, 14],
                [29, 26, 23, 17, 13],
                [29, 26, 22, 20, 16],
                [29, 26, 22, 20, 15],
                [29, 26, 22, 20, 14],
                [29, 26, 22, 20, 13],
                [29, 26, 22, 19, 16],
                [29, 26, 22, 19, 15],
                [29, 26, 22, 19, 14],
                [29, 26, 22, 19, 13],
                [29, 26, 22, 18, 16],
                [29, 26, 22, 18, 15],
                [29, 26, 22, 18, 14],
                [29, 26, 22, 18, 13],
                [29, 26, 22, 17, 16],
                [29, 26, 22, 17, 15],
                [29, 26, 22, 17, 14],
                [29, 26, 22, 17, 13],
                [29, 26, 21, 20, 16],
                [29, 26, 21, 20, 15],
                [29, 26, 21, 20, 14],
                [29, 26, 21, 20, 13],
                [29, 26, 21, 19, 16],
                [29, 26, 21, 19, 15],
                [29, 26, 21, 19, 14],
                [29, 26, 21, 19, 13],
                [29, 26, 21, 18, 16],
                [29, 26, 21, 18, 15],
                [29, 26, 21, 18, 14],
                [29, 26, 21, 18, 13],
                [29, 26, 21, 17, 16],
                [29, 26, 21, 17, 15],
                [29, 26, 21, 17, 14],
                [29, 26, 21, 17, 13],
                [29, 25, 24, 20, 16],
                [29, 25, 24, 20, 15],
                [29, 25, 24, 20, 14],
                [29, 25, 24, 20, 13],
                [29, 25, 24, 19, 16],
                [29, 25, 24, 19, 15],
                [29, 25, 24, 19, 14],
                [29, 25, 24, 19, 13],
                [29, 25, 24, 18, 16],
                [29, 25, 24, 18, 15],
                [29, 25, 24, 18, 14],
                [29, 25, 24, 18, 13],
                [29, 25, 24, 17, 16],
                [29, 25, 24, 17, 15],
                [29, 25, 24, 17, 14],
                [29, 25, 24, 17, 13],
                [29, 25, 23, 20, 16],
                [29, 25, 23, 20, 15],
                [29, 25, 23, 20, 14],
                [29, 25, 23, 20, 13],
                [29, 25, 23, 19, 16],
                [29, 25, 23, 19, 15],
                [29, 25, 23, 19, 14],
                [29, 25, 23, 19, 13],
                [29, 25, 23, 18, 16],
                [29, 25, 23, 18, 15],
                [29, 25, 23, 18, 14],
                [29, 25, 23, 18, 13],
                [29, 25, 23, 17, 16],
                [29, 25, 23, 17, 15],
                [29, 25, 23, 17, 14],
                [29, 25, 23, 17, 13],
                [29, 25, 22, 20, 16],
                [29, 25, 22, 20, 15],
                [29, 25, 22, 20, 14],
                [29, 25, 22, 20, 13],
                [29, 25, 22, 19, 16],
                [29, 25, 22, 19, 15],
                [29, 25, 22, 19, 14],
                [29, 25, 22, 19, 13],
                [29, 25, 22, 18, 16],
                [29, 25, 22, 18, 15],
                [29, 25, 22, 18, 14],
                [29, 25, 22, 18, 13],
                [29, 25, 22, 17, 16],
                [29, 25, 22, 17, 15],
                [29, 25, 22, 17, 14],
                [29, 25, 22, 17, 13],
                [29, 25, 21, 20, 16],
                [29, 25, 21, 20, 15],
                [29, 25, 21, 20, 14],
                [29, 25, 21, 20, 13],
                [29, 25, 21, 19, 16],
                [29, 25, 21, 19, 15],
                [29, 25, 21, 19, 14],
                [29, 25, 21, 19, 13],
                [29, 25, 21, 18, 16],
                [29, 25, 21, 18, 15],
                [29, 25, 21, 18, 14],
                [29, 25, 21, 18, 13],
                [29, 25, 21, 17, 16],
                [29, 25, 21, 17, 15],
                [29, 25, 21, 17, 14],
                [28, 24, 20, 16, 11],
                [28, 24, 20, 16, 10],
                [28, 24, 20, 16, 9],
                [28, 24, 20, 15, 12],
                [28, 24, 20, 15, 11],
                [28, 24, 20, 15, 10],
                [28, 24, 20, 15, 9],
                [28, 24, 20, 14, 12],
                [28, 24, 20, 14, 11],
                [28, 24, 20, 14, 10],
                [28, 24, 20, 14, 9],
                [28, 24, 20, 13, 12],
                [28, 24, 20, 13, 11],
                [28, 24, 20, 13, 10],
                [28, 24, 20, 13, 9],
                [28, 24, 19, 16, 12],
                [28, 24, 19, 16, 11],
                [28, 24, 19, 16, 10],
                [28, 24, 19, 16, 9],
                [28, 24, 19, 15, 12],
                [28, 24, 19, 15, 11],
                [28, 24, 19, 15, 10],
                [28, 24, 19, 15, 9],
                [28, 24, 19, 14, 12],
                [28, 24, 19, 14, 11],
                [28, 24, 19, 14, 10],
                [28, 24, 19, 14, 9],
                [28, 24, 19, 13, 12],
                [28, 24, 19, 13, 11],
                [28, 24, 19, 13, 10],
                [28, 24, 19, 13, 9],
                [28, 24, 18, 16, 12],
                [28, 24, 18, 16, 11],
                [28, 24, 18, 16, 10],
                [28, 24, 18, 16, 9],
                [28, 24, 18, 15, 12],
                [28, 24, 18, 15, 11],
                [28, 24, 18, 15, 10],
                [28, 24, 18, 15, 9],
                [28, 24, 18, 14, 12],
                [28, 24, 18, 14, 11],
                [28, 24, 18, 14, 10],
                [28, 24, 18, 14, 9],
                [28, 24, 18, 13, 12],
                [28, 24, 18, 13, 11],
                [28, 24, 18, 13, 10],
                [28, 24, 18, 13, 9],
                [28, 24, 17, 16, 12],
                [28, 24, 17, 16, 11],
                [28, 24, 17, 16, 10],
                [28, 24, 17, 16, 9],
                [28, 24, 17, 15, 12],
                [28, 24, 17, 15, 11],
                [28, 24, 17, 15, 10],
                [28, 24, 17, 15, 9],
                [28, 24, 17, 14, 12],
                [28, 24, 17, 14, 11],
                [28, 24, 17, 14, 10],
                [28, 24, 17, 14, 9],
                [28, 24, 17, 13, 12],
                [28, 24, 17, 13, 11],
                [28, 24, 17, 13, 10],
                [28, 24, 17, 13, 9],
                [28, 23, 20, 16, 12],
                [28, 23, 20, 16, 11],
                [28, 23, 20, 16, 10],
                [28, 23, 20, 16, 9],
                [28, 23, 20, 15, 12],
                [28, 23, 20, 15, 11],
                [28, 23, 20, 15, 10],
                [28, 23, 20, 15, 9],
                [28, 23, 20, 14, 12],
                [28, 23, 20, 14, 11],
                [28, 23, 20, 14, 10],
                [28, 23, 20, 14, 9],
                [28, 23, 20, 13, 12],
                [28, 23, 20, 13, 11],
                [28, 23, 20, 13, 10],
                [28, 23, 20, 13, 9],
                [28, 23, 19, 16, 12],
                [28, 23, 19, 16, 11],
                [28, 23, 19, 16, 10],
                [28, 23, 19, 16, 9],
                [28, 23, 19, 15, 12],
                [28, 23, 19, 15, 11],
                [28, 23, 19, 15, 10],
                [28, 23, 19, 15, 9],
                [28, 23, 19, 14, 12],
                [28, 23, 19, 14, 11],
                [28, 23, 19, 14, 10],
                [28, 23, 19, 14, 9],
                [28, 23, 19, 13, 12],
                [28, 23, 19, 13, 11],
                [28, 23, 19, 13, 10],
                [28, 23, 19, 13, 9],
                [28, 23, 18, 16, 12],
                [28, 23, 18, 16, 11],
                [28, 23, 18, 16, 10],
                [28, 23, 18, 16, 9],
                [28, 23, 18, 15, 12],
                [28, 23, 18, 15, 11],
                [28, 23, 18, 15, 10],
                [28, 23, 18, 15, 9],
                [28, 23, 18, 14, 12],
                [28, 23, 18, 14, 11],
                [28, 23, 18, 14, 10],
                [28, 23, 18, 14, 9],
                [28, 23, 18, 13, 12],
                [28, 23, 18, 13, 11],
                [28, 23, 18, 13, 10],
                [28, 23, 18, 13, 9],
                [28, 23, 17, 16, 12],
                [28, 23, 17, 16, 11],
                [28, 23, 17, 16, 10],
                [28, 23, 17, 16, 9],
                [28, 23, 17, 15, 12],
                [28, 23, 17, 15, 11],
                [28, 23, 17, 15, 10],
                [28, 23, 17, 15, 9],
                [28, 23, 17, 14, 12],
                [28, 23, 17, 14, 11],
                [28, 23, 17, 14, 10],
                [28, 23, 17, 14, 9],
                [28, 23, 17, 13, 12],
                [28, 23, 17, 13, 11],
                [28, 23, 17, 13, 10],
                [28, 23, 17, 13, 9],
                [28, 22, 20, 16, 12],
                [28, 22, 20, 16, 11],
                [28, 22, 20, 16, 10],
                [28, 22, 20, 16, 9],
                [28, 22, 20, 15, 12],
                [28, 22, 20, 15, 11],
                [28, 22, 20, 15, 10],
                [28, 22, 20, 15, 9],
                [28, 22, 20, 14, 12],
                [28, 22, 20, 14, 11],
                [28, 22, 20, 14, 10],
                [28, 22, 20, 14, 9],
                [28, 22, 20, 13, 12],
                [28, 22, 20, 13, 11],
                [28, 22, 20, 13, 10],
                [28, 22, 20, 13, 9],
                [28, 22, 19, 16, 12],
                [28, 22, 19, 16, 11],
                [28, 22, 19, 16, 10],
                [28, 22, 19, 16, 9],
                [28, 22, 19, 15, 12],
                [28, 22, 19, 15, 11],
                [28, 22, 19, 15, 10],
                [28, 22, 19, 15, 9],
                [28, 22, 19, 14, 12],
                [28, 22, 19, 14, 11],
                [28, 22, 19, 14, 10],
                [28, 22, 19, 14, 9],
                [28, 22, 19, 13, 12],
                [28, 22, 19, 13, 11],
                [28, 22, 19, 13, 10],
                [28, 22, 19, 13, 9],
                [28, 22, 18, 16, 12],
                [28, 22, 18, 16, 11],
                [28, 22, 18, 16, 10],
                [28, 22, 18, 16, 9],
                [28, 22, 18, 15, 12],
                [28, 22, 18, 15, 11],
                [28, 22, 18, 15, 10],
                [28, 22, 18, 15, 9],
                [28, 22, 18, 14, 12],
                [28, 22, 18, 14, 11],
                [28, 22, 18, 14, 10],
                [28, 22, 18, 14, 9],
                [28, 22, 18, 13, 12],
                [28, 22, 18, 13, 11],
                [28, 22, 18, 13, 10],
                [28, 22, 18, 13, 9],
                [28, 22, 17, 16, 12],
                [28, 22, 17, 16, 11],
                [28, 22, 17, 16, 10],
                [28, 22, 17, 16, 9],
                [28, 22, 17, 15, 12],
                [28, 22, 17, 15, 11],
                [28, 22, 17, 15, 10],
                [28, 22, 17, 15, 9],
                [28, 22, 17, 14, 12],
                [28, 22, 17, 14, 11],
                [28, 22, 17, 14, 10],
                [28, 22, 17, 14, 9],
                [28, 22, 17, 13, 12],
                [28, 22, 17, 13, 11],
                [28, 22, 17, 13, 10],
                [28, 22, 17, 13, 9],
                [28, 21, 20, 16, 12],
                [28, 21, 20, 16, 11],
                [28, 21, 20, 16, 10],
                [28, 21, 20, 16, 9],
                [28, 21, 20, 15, 12],
                [28, 21, 20, 15, 11],
                [28, 21, 20, 15, 10],
                [28, 21, 20, 15, 9],
                [28, 21, 20, 14, 12],
                [28, 21, 20, 14, 11],
                [28, 21, 20, 14, 10],
                [28, 21, 20, 14, 9],
                [28, 21, 20, 13, 12],
                [28, 21, 20, 13, 11],
                [28, 21, 20, 13, 10],
                [28, 21, 20, 13, 9],
                [28, 21, 19, 16, 12],
                [28, 21, 19, 16, 11],
                [28, 21, 19, 16, 10],
                [28, 21, 19, 16, 9],
                [28, 21, 19, 15, 12],
                [28, 21, 19, 15, 11],
                [28, 21, 19, 15, 10],
                [28, 21, 19, 15, 9],
                [28, 21, 19, 14, 12],
                [28, 21, 19, 14, 11],
                [28, 21, 19, 14, 10],
                [28, 21, 19, 14, 9],
                [28, 21, 19, 13, 12],
                [28, 21, 19, 13, 11],
                [28, 21, 19, 13, 10],
                [28, 21, 19, 13, 9],
                [28, 21, 18, 16, 12],
                [28, 21, 18, 16, 11],
                [28, 21, 18, 16, 10],
                [28, 21, 18, 16, 9],
                [28, 21, 18, 15, 12],
                [28, 21, 18, 15, 11],
                [28, 21, 18, 15, 10],
                [28, 21, 18, 15, 9],
                [28, 21, 18, 14, 12],
                [28, 21, 18, 14, 11],
                [28, 21, 18, 14, 10],
                [28, 21, 18, 14, 9],
                [28, 21, 18, 13, 12],
                [28, 21, 18, 13, 11],
                [28, 21, 18, 13, 10],
                [28, 21, 18, 13, 9],
                [28, 21, 17, 16, 12],
                [28, 21, 17, 16, 11],
                [28, 21, 17, 16, 10],
                [28, 21, 17, 16, 9],
                [28, 21, 17, 15, 12],
                [28, 21, 17, 15, 11],
                [28, 21, 17, 15, 10],
                [28, 21, 17, 15, 9],
                [28, 21, 17, 14, 12],
                [28, 21, 17, 14, 11],
                [28, 21, 17, 14, 10],
                [28, 21, 17, 14, 9],
                [28, 21, 17, 13, 12],
                [28, 21, 17, 13, 11],
                [28, 21, 17, 13, 10],
                [28, 21, 17, 13, 9],
                [27, 24, 20, 16, 12],
                [27, 24, 20, 16, 11],
                [27, 24, 20, 16, 10],
                [27, 24, 20, 16, 9],
                [27, 24, 20, 15, 12],
                [27, 24, 20, 15, 11],
                [27, 24, 20, 15, 10],
                [27, 24, 20, 15, 9],
                [27, 24, 20, 14, 12],
                [27, 24, 20, 14, 11],
                [27, 24, 20, 14, 10],
                [27, 24, 20, 14, 9],
                [27, 24, 20, 13, 12],
                [27, 24, 20, 13, 11],
                [27, 24, 20, 13, 10],
                [27, 24, 20, 13, 9],
                [27, 24, 19, 16, 12],
                [27, 24, 19, 16, 11],
                [27, 24, 19, 16, 10],
                [27, 24, 19, 16, 9],
                [27, 24, 19, 15, 12],
                [27, 24, 19, 15, 11],
                [27, 24, 19, 15, 10],
                [27, 24, 19, 15, 9],
                [27, 24, 19, 14, 12],
                [27, 24, 19, 14, 11],
                [27, 24, 19, 14, 10],
                [27, 24, 19, 14, 9],
                [27, 24, 19, 13, 12],
                [27, 24, 19, 13, 11],
                [27, 24, 19, 13, 10],
                [27, 24, 19, 13, 9],
                [27, 24, 18, 16, 12],
                [27, 24, 18, 16, 11],
                [27, 24, 18, 16, 10],
                [27, 24, 18, 16, 9],
                [27, 24, 18, 15, 12],
                [27, 24, 18, 15, 11],
                [27, 24, 18, 15, 10],
                [27, 24, 18, 15, 9],
                [27, 24, 18, 14, 12],
                [27, 24, 18, 14, 11],
                [27, 24, 18, 14, 10],
                [27, 24, 18, 14, 9],
                [27, 24, 18, 13, 12],
                [27, 24, 18, 13, 11],
                [27, 24, 18, 13, 10],
                [27, 24, 18, 13, 9],
                [27, 24, 17, 16, 12],
                [27, 24, 17, 16, 11],
                [27, 24, 17, 16, 10],
                [27, 24, 17, 16, 9],
                [27, 24, 17, 15, 12],
                [27, 24, 17, 15, 11],
                [27, 24, 17, 15, 10],
                [27, 24, 17, 15, 9],
                [27, 24, 17, 14, 12],
                [27, 24, 17, 14, 11],
                [27, 24, 17, 14, 10],
                [27, 24, 17, 14, 9],
                [27, 24, 17, 13, 12],
                [27, 24, 17, 13, 11],
                [27, 24, 17, 13, 10],
                [27, 24, 17, 13, 9],
                [27, 23, 20, 16, 12],
                [27, 23, 20, 16, 11],
                [27, 23, 20, 16, 10],
                [27, 23, 20, 16, 9],
                [27, 23, 20, 15, 12],
                [27, 23, 20, 15, 11],
                [27, 23, 20, 15, 10],
                [27, 23, 20, 15, 9],
                [27, 23, 20, 14, 12],
                [27, 23, 20, 14, 11],
                [27, 23, 20, 14, 10],
                [27, 23, 20, 14, 9],
                [27, 23, 20, 13, 12],
                [27, 23, 20, 13, 11],
                [27, 23, 20, 13, 10],
                [27, 23, 20, 13, 9],
                [27, 23, 19, 16, 12],
                [27, 23, 19, 16, 11],
                [27, 23, 19, 16, 10],
                [27, 23, 19, 16, 9],
                [27, 23, 19, 15, 12],
                [27, 23, 19, 15, 10],
                [27, 23, 19, 15, 9],
                [27, 23, 19, 14, 12],
                [27, 23, 19, 14, 11],
                [27, 23, 19, 14, 10],
                [27, 23, 19, 14, 9],
                [27, 23, 19, 13, 12],
                [27, 23, 19, 13, 11],
                [27, 23, 19, 13, 10],
                [27, 23, 19, 13, 9],
                [27, 23, 18, 16, 12],
                [27, 23, 18, 16, 11],
                [27, 23, 18, 16, 10],
                [27, 23, 18, 16, 9],
                [27, 23, 18, 15, 12],
                [27, 23, 18, 15, 11],
                [27, 23, 18, 15, 10],
                [27, 23, 18, 15, 9],
                [27, 23, 18, 14, 12],
                [27, 23, 18, 14, 11],
                [27, 23, 18, 14, 10],
                [27, 23, 18, 14, 9],
                [27, 23, 18, 13, 12],
                [27, 23, 18, 13, 11],
                [27, 23, 18, 13, 10],
                [27, 23, 18, 13, 9],
                [27, 23, 17, 16, 12],
                [27, 23, 17, 16, 11],
                [27, 23, 17, 16, 10],
                [27, 23, 17, 16, 9],
                [27, 23, 17, 15, 12],
                [27, 23, 17, 15, 11],
                [27, 23, 17, 15, 10],
                [27, 23, 17, 15, 9],
                [27, 23, 17, 14, 12],
                [27, 23, 17, 14, 11],
                [27, 23, 17, 14, 10],
                [27, 23, 17, 14, 9],
                [27, 23, 17, 13, 12],
                [27, 23, 17, 13, 11],
                [27, 23, 17, 13, 10],
                [27, 23, 17, 13, 9],
                [27, 22, 20, 16, 12],
                [27, 22, 20, 16, 11],
                [27, 22, 20, 16, 10],
                [27, 22, 20, 16, 9],
                [27, 22, 20, 15, 12],
                [27, 22, 20, 15, 11],
                [27, 22, 20, 15, 10],
                [27, 22, 20, 15, 9],
                [27, 22, 20, 14, 12],
                [27, 22, 20, 14, 11],
                [27, 22, 20, 14, 10],
                [27, 22, 20, 14, 9],
                [27, 22, 20, 13, 12],
                [27, 22, 20, 13, 11],
                [27, 22, 20, 13, 10],
                [27, 22, 20, 13, 9],
                [27, 22, 19, 16, 12],
                [27, 22, 19, 16, 11],
                [27, 22, 19, 16, 10],
                [27, 22, 19, 16, 9],
                [27, 22, 19, 15, 12],
                [27, 22, 19, 15, 11],
                [27, 22, 19, 15, 10],
                [27, 22, 19, 15, 9],
                [27, 22, 19, 14, 12],
                [27, 22, 19, 14, 11],
                [27, 22, 19, 14, 10],
                [27, 22, 19, 14, 9],
                [27, 22, 19, 13, 12],
                [27, 22, 19, 13, 11],
                [27, 22, 19, 13, 10],
                [27, 22, 19, 13, 9],
                [27, 22, 18, 16, 12],
                [27, 22, 18, 16, 11],
                [27, 22, 18, 16, 10],
                [27, 22, 18, 16, 9],
                [27, 22, 18, 15, 12],
                [27, 22, 18, 15, 11],
                [27, 22, 18, 15, 10],
                [27, 22, 18, 15, 9],
                [27, 22, 18, 14, 12],
                [27, 22, 18, 14, 11],
                [27, 22, 18, 14, 10],
                [27, 22, 18, 14, 9],
                [27, 22, 18, 13, 12],
                [27, 22, 18, 13, 11],
                [27, 22, 18, 13, 10],
                [27, 22, 18, 13, 9],
                [27, 22, 17, 16, 12],
                [27, 22, 17, 16, 11],
                [27, 22, 17, 16, 10],
                [27, 22, 17, 16, 9],
                [27, 22, 17, 15, 12],
                [27, 22, 17, 15, 11],
                [27, 22, 17, 15, 10],
                [27, 22, 17, 15, 9],
                [27, 22, 17, 14, 12],
                [27, 22, 17, 14, 11],
                [27, 22, 17, 14, 10],
                [27, 22, 17, 14, 9],
                [27, 22, 17, 13, 12],
                [27, 22, 17, 13, 11],
                [27, 22, 17, 13, 10],
                [27, 22, 17, 13, 9],
                [27, 21, 20, 16, 12],
                [27, 21, 20, 16, 11],
                [27, 21, 20, 16, 10],
                [27, 21, 20, 16, 9],
                [27, 21, 20, 15, 12],
                [27, 21, 20, 15, 11],
                [27, 21, 20, 15, 10],
                [27, 21, 20, 15, 9],
                [27, 21, 20, 14, 12],
                [27, 21, 20, 14, 11],
                [27, 21, 20, 14, 10],
                [27, 21, 20, 14, 9],
                [27, 21, 20, 13, 12],
                [27, 21, 20, 13, 11],
                [27, 21, 20, 13, 10],
                [27, 21, 20, 13, 9],
                [27, 21, 19, 16, 12],
                [27, 21, 19, 16, 11],
                [27, 21, 19, 16, 10],
                [27, 21, 19, 16, 9],
                [27, 21, 19, 15, 12],
                [27, 21, 19, 15, 11],
                [27, 21, 19, 15, 10],
                [27, 21, 19, 15, 9],
                [27, 21, 19, 14, 12],
                [27, 21, 19, 14, 11],
                [27, 21, 19, 14, 10],
                [27, 21, 19, 14, 9],
                [27, 21, 19, 13, 12],
                [27, 21, 19, 13, 11],
                [27, 21, 19, 13, 10],
                [27, 21, 19, 13, 9],
                [27, 21, 18, 16, 12],
                [27, 21, 18, 16, 11],
                [27, 21, 18, 16, 10],
                [27, 21, 18, 16, 9],
                [27, 21, 18, 15, 12],
                [27, 21, 18, 15, 11],
                [27, 21, 18, 15, 10],
                [27, 21, 18, 15, 9],
                [27, 21, 18, 14, 12],
                [27, 21, 18, 14, 11],
                [27, 21, 18, 14, 10],
                [27, 21, 18, 14, 9],
                [27, 21, 18, 13, 12],
                [27, 21, 18, 13, 11],
                [27, 21, 18, 13, 10],
                [27, 21, 18, 13, 9],
                [27, 21, 17, 16, 12],
                [27, 21, 17, 16, 11],
                [27, 21, 17, 16, 10],
                [27, 21, 17, 16, 9],
                [27, 21, 17, 15, 12],
                [27, 21, 17, 15, 11],
                [27, 21, 17, 15, 10],
                [27, 21, 17, 15, 9],
                [27, 21, 17, 14, 12],
                [27, 21, 17, 14, 11],
                [27, 21, 17, 14, 10],
                [27, 21, 17, 14, 9],
                [27, 21, 17, 13, 12],
                [27, 21, 17, 13, 11],
                [27, 21, 17, 13, 10],
                [27, 21, 17, 13, 9],
                [26, 24, 20, 16, 12],
                [26, 24, 20, 16, 11],
                [26, 24, 20, 16, 10],
                [26, 24, 20, 16, 9],
                [26, 24, 20, 15, 12],
                [26, 24, 20, 15, 11],
                [26, 24, 20, 15, 10],
                [26, 24, 20, 15, 9],
                [26, 24, 20, 14, 12],
                [26, 24, 20, 14, 11],
                [26, 24, 20, 14, 10],
                [26, 24, 20, 14, 9],
                [26, 24, 20, 13, 12],
                [26, 24, 20, 13, 11],
                [26, 24, 20, 13, 10],
                [26, 24, 20, 13, 9],
                [26, 24, 19, 16, 12],
                [26, 24, 19, 16, 11],
                [26, 24, 19, 16, 10],
                [26, 24, 19, 16, 9],
                [26, 24, 19, 15, 12],
                [26, 24, 19, 15, 11],
                [26, 24, 19, 15, 10],
                [26, 24, 19, 15, 9],
                [26, 24, 19, 14, 12],
                [26, 24, 19, 14, 11],
                [26, 24, 19, 14, 10],
                [26, 24, 19, 14, 9],
                [26, 24, 19, 13, 12],
                [26, 24, 19, 13, 11],
                [26, 24, 19, 13, 10],
                [26, 24, 19, 13, 9],
                [26, 24, 18, 16, 12],
                [26, 24, 18, 16, 11],
                [26, 24, 18, 16, 10],
                [26, 24, 18, 16, 9],
                [26, 24, 18, 15, 12],
                [26, 24, 18, 15, 11],
                [26, 24, 18, 15, 10],
                [26, 24, 18, 15, 9],
                [26, 24, 18, 14, 12],
                [26, 24, 18, 14, 11],
                [26, 24, 18, 14, 10],
                [26, 24, 18, 14, 9],
                [26, 24, 18, 13, 12],
                [26, 24, 18, 13, 11],
                [26, 24, 18, 13, 10],
                [26, 24, 18, 13, 9],
                [26, 24, 17, 16, 12],
                [26, 24, 17, 16, 11],
                [26, 24, 17, 16, 10],
                [26, 24, 17, 16, 9],
                [26, 24, 17, 15, 12],
                [26, 24, 17, 15, 11],
                [26, 24, 17, 15, 10],
                [26, 24, 17, 15, 9],
                [26, 24, 17, 14, 12],
                [26, 24, 17, 14, 11],
                [26, 24, 17, 14, 10],
                [26, 24, 17, 14, 9],
                [26, 24, 17, 13, 12],
                [26, 24, 17, 13, 11],
                [26, 24, 17, 13, 10],
                [26, 24, 17, 13, 9],
                [26, 23, 20, 16, 12],
                [26, 23, 20, 16, 11],
                [26, 23, 20, 16, 10],
                [26, 23, 20, 16, 9],
                [26, 23, 20, 15, 12],
                [26, 23, 20, 15, 11],
                [26, 23, 20, 15, 10],
                [26, 23, 20, 15, 9],
                [26, 23, 20, 14, 12],
                [26, 23, 20, 14, 11],
                [26, 23, 20, 14, 10],
                [26, 23, 20, 14, 9],
                [26, 23, 20, 13, 12],
                [26, 23, 20, 13, 11],
                [26, 23, 20, 13, 10],
                [26, 23, 20, 13, 9],
                [26, 23, 19, 16, 12],
                [26, 23, 19, 16, 11],
                [26, 23, 19, 16, 10],
                [26, 23, 19, 16, 9],
                [26, 23, 19, 15, 12],
                [26, 23, 19, 15, 11],
                [26, 23, 19, 15, 10],
                [26, 23, 19, 15, 9],
                [26, 23, 19, 14, 12],
                [26, 23, 19, 14, 11],
                [26, 23, 19, 14, 10],
                [26, 23, 19, 14, 9],
                [26, 23, 19, 13, 12],
                [26, 23, 19, 13, 11],
                [26, 23, 19, 13, 10],
                [26, 23, 19, 13, 9],
                [26, 23, 18, 16, 12],
                [26, 23, 18, 16, 11],
                [26, 23, 18, 16, 10],
                [26, 23, 18, 16, 9],
                [26, 23, 18, 15, 12],
                [26, 23, 18, 15, 11],
                [26, 23, 18, 15, 10],
                [26, 23, 18, 15, 9],
                [26, 23, 18, 14, 12],
                [26, 23, 18, 14, 11],
                [26, 23, 18, 14, 10],
                [26, 23, 18, 14, 9],
                [26, 23, 18, 13, 12],
                [26, 23, 18, 13, 11],
                [26, 23, 18, 13, 10],
                [26, 23, 18, 13, 9],
                [26, 23, 17, 16, 12],
                [26, 23, 17, 16, 11],
                [26, 23, 17, 16, 10],
                [26, 23, 17, 16, 9],
                [26, 23, 17, 15, 12],
                [26, 23, 17, 15, 11],
                [26, 23, 17, 15, 10],
                [26, 23, 17, 15, 9],
                [26, 23, 17, 14, 12],
                [26, 23, 17, 14, 11],
                [26, 23, 17, 14, 10],
                [26, 23, 17, 14, 9],
                [26, 23, 17, 13, 12],
                [26, 23, 17, 13, 11],
                [26, 23, 17, 13, 10],
                [26, 23, 17, 13, 9],
                [26, 22, 20, 16, 12],
                [26, 22, 20, 16, 11],
                [26, 22, 20, 16, 10],
                [26, 22, 20, 16, 9],
                [26, 22, 20, 15, 12],
                [26, 22, 20, 15, 11],
                [26, 22, 20, 15, 10],
                [26, 22, 20, 15, 9],
                [26, 22, 20, 14, 12],
                [26, 22, 20, 14, 11],
                [26, 22, 20, 14, 10],
                [26, 22, 20, 14, 9],
                [26, 22, 20, 13, 12],
                [26, 22, 20, 13, 11],
                [26, 22, 20, 13, 10],
                [26, 22, 20, 13, 9],
                [26, 22, 19, 16, 12],
                [26, 22, 19, 16, 11],
                [26, 22, 19, 16, 10],
                [26, 22, 19, 16, 9],
                [26, 22, 19, 15, 12],
                [26, 22, 19, 15, 11],
                [26, 22, 19, 15, 10],
                [26, 22, 19, 15, 9],
                [26, 22, 19, 14, 12],
                [26, 22, 19, 14, 11],
                [26, 22, 19, 14, 10],
                [26, 22, 19, 14, 9],
                [26, 22, 19, 13, 12],
                [26, 22, 19, 13, 11],
                [26, 22, 19, 13, 10],
                [26, 22, 19, 13, 9],
                [26, 22, 18, 16, 12],
                [26, 22, 18, 16, 11],
                [26, 22, 18, 16, 10],
                [26, 22, 18, 16, 9],
                [26, 22, 18, 15, 12],
                [26, 22, 18, 15, 11],
                [26, 22, 18, 15, 10],
                [26, 22, 18, 15, 9],
                [26, 22, 18, 14, 12],
                [26, 22, 18, 14, 11],
                [26, 22, 18, 14, 9],
                [26, 22, 18, 13, 12],
                [26, 22, 18, 13, 11],
                [26, 22, 18, 13, 10],
                [26, 22, 18, 13, 9],
                [26, 22, 17, 16, 12],
                [26, 22, 17, 16, 11],
                [26, 22, 17, 16, 10],
                [26, 22, 17, 16, 9],
                [26, 22, 17, 15, 12],
                [26, 22, 17, 15, 11],
                [26, 22, 17, 15, 10],
                [26, 22, 17, 15, 9],
                [26, 22, 17, 14, 12],
                [26, 22, 17, 14, 11],
                [26, 22, 17, 14, 10],
                [26, 22, 17, 14, 9],
                [26, 22, 17, 13, 12],
                [26, 22, 17, 13, 11],
                [26, 22, 17, 13, 10],
                [26, 22, 17, 13, 9],
                [26, 21, 20, 16, 12],
                [26, 21, 20, 16, 11],
                [26, 21, 20, 16, 10],
                [26, 21, 20, 16, 9],
                [26, 21, 20, 15, 12],
                [26, 21, 20, 15, 11],
                [26, 21, 20, 15, 10],
                [26, 21, 20, 15, 9],
                [26, 21, 20, 14, 12],
                [26, 21, 20, 14, 11],
                [26, 21, 20, 14, 10],
                [26, 21, 20, 14, 9],
                [26, 21, 20, 13, 12],
                [26, 21, 20, 13, 11],
                [26, 21, 20, 13, 10],
                [26, 21, 20, 13, 9],
                [26, 21, 19, 16, 12],
                [26, 21, 19, 16, 11],
                [26, 21, 19, 16, 10],
                [26, 21, 19, 16, 9],
                [26, 21, 19, 15, 12],
                [26, 21, 19, 15, 11],
                [26, 21, 19, 15, 10],
                [26, 21, 19, 15, 9],
                [26, 21, 19, 14, 12],
                [26, 21, 19, 14, 11],
                [26, 21, 19, 14, 10],
                [26, 21, 19, 14, 9],
                [26, 21, 19, 13, 12],
                [26, 21, 19, 13, 11],
                [26, 21, 19, 13, 10],
                [26, 21, 19, 13, 9],
                [26, 21, 18, 16, 12],
                [26, 21, 18, 16, 11],
                [26, 21, 18, 16, 10],
                [26, 21, 18, 16, 9],
                [26, 21, 18, 15, 12],
                [26, 21, 18, 15, 11],
                [26, 21, 18, 15, 10],
                [26, 21, 18, 15, 9],
                [26, 21, 18, 14, 12],
                [26, 21, 18, 14, 11],
                [26, 21, 18, 14, 10],
                [26, 21, 18, 14, 9],
                [26, 21, 18, 13, 12],
                [26, 21, 18, 13, 11],
                [26, 21, 18, 13, 10],
                [26, 21, 18, 13, 9],
                [26, 21, 17, 16, 12],
                [26, 21, 17, 16, 11],
                [26, 21, 17, 16, 10],
                [26, 21, 17, 16, 9],
                [26, 21, 17, 15, 12],
                [26, 21, 17, 15, 11],
                [26, 21, 17, 15, 10],
                [26, 21, 17, 15, 9],
                [26, 21, 17, 14, 12],
                [26, 21, 17, 14, 11],
                [26, 21, 17, 14, 10],
                [26, 21, 17, 14, 9],
                [26, 21, 17, 13, 12],
                [26, 21, 17, 13, 11],
                [26, 21, 17, 13, 10],
                [26, 21, 17, 13, 9],
                [25, 24, 20, 16, 12],
                [25, 24, 20, 16, 11],
                [25, 24, 20, 16, 10],
                [25, 24, 20, 16, 9],
                [25, 24, 20, 15, 12],
                [25, 24, 20, 15, 11],
                [25, 24, 20, 15, 10],
                [25, 24, 20, 15, 9],
                [25, 24, 20, 14, 12],
                [25, 24, 20, 14, 11],
                [25, 24, 20, 14, 10],
                [25, 24, 20, 14, 9],
                [25, 24, 20, 13, 12],
                [25, 24, 20, 13, 11],
                [25, 24, 20, 13, 10],
                [25, 24, 20, 13, 9],
                [25, 24, 19, 16, 12],
                [25, 24, 19, 16, 11],
                [25, 24, 19, 16, 10],
                [25, 24, 19, 16, 9],
                [25, 24, 19, 15, 12],
                [25, 24, 19, 15, 11],
                [25, 24, 19, 15, 10],
                [25, 24, 19, 15, 9],
                [25, 24, 19, 14, 12],
                [25, 24, 19, 14, 11],
                [25, 24, 19, 14, 10],
                [25, 24, 19, 14, 9],
                [25, 24, 19, 13, 12],
                [25, 24, 19, 13, 11],
                [25, 24, 19, 13, 10],
                [25, 24, 19, 13, 9],
                [25, 24, 18, 16, 12],
                [25, 24, 18, 16, 11],
                [25, 24, 18, 16, 10],
                [25, 24, 18, 16, 9],
                [25, 24, 18, 15, 12],
                [25, 24, 18, 15, 11],
                [25, 24, 18, 15, 10],
                [25, 24, 18, 15, 9],
                [25, 24, 18, 14, 12],
                [25, 24, 18, 14, 11],
                [25, 24, 18, 14, 10],
                [25, 24, 18, 14, 9],
                [25, 24, 18, 13, 12],
                [25, 24, 18, 13, 11],
                [25, 24, 18, 13, 10],
                [25, 24, 18, 13, 9],
                [25, 24, 17, 16, 12],
                [25, 24, 17, 16, 11],
                [25, 24, 17, 16, 10],
                [25, 24, 17, 16, 9],
                [25, 24, 17, 15, 12],
                [25, 24, 17, 15, 11],
                [25, 24, 17, 15, 10],
                [25, 24, 17, 15, 9],
                [25, 24, 17, 14, 12],
                [25, 24, 17, 14, 11],
                [25, 24, 17, 14, 10],
                [25, 24, 17, 14, 9],
                [25, 24, 17, 13, 12],
                [25, 24, 17, 13, 11],
                [25, 24, 17, 13, 10],
                [25, 24, 17, 13, 9],
                [25, 23, 20, 16, 12],
                [25, 23, 20, 16, 11],
                [25, 23, 20, 16, 10],
                [25, 23, 20, 16, 9],
                [25, 23, 20, 15, 12],
                [25, 23, 20, 15, 11],
                [25, 23, 20, 15, 10],
                [25, 23, 20, 15, 9],
                [25, 23, 20, 14, 12],
                [25, 23, 20, 14, 11],
                [25, 23, 20, 14, 10],
                [25, 23, 20, 14, 9],
                [25, 23, 20, 13, 12],
                [25, 23, 20, 13, 11],
                [25, 23, 20, 13, 10],
                [25, 23, 20, 13, 9],
                [25, 23, 19, 16, 12],
                [25, 23, 19, 16, 11],
                [25, 23, 19, 16, 10],
                [25, 23, 19, 16, 9],
                [25, 23, 19, 15, 12],
                [25, 23, 19, 15, 11],
                [25, 23, 19, 15, 10],
                [25, 23, 19, 15, 9],
                [25, 23, 19, 14, 12],
                [25, 23, 19, 14, 11],
                [25, 23, 19, 14, 10],
                [25, 23, 19, 14, 9],
                [25, 23, 19, 13, 12],
                [25, 23, 19, 13, 11],
                [25, 23, 19, 13, 10],
                [25, 23, 19, 13, 9],
                [25, 23, 18, 16, 12],
                [25, 23, 18, 16, 11],
                [25, 23, 18, 16, 10],
                [25, 23, 18, 16, 9],
                [25, 23, 18, 15, 12],
                [25, 23, 18, 15, 11],
                [25, 23, 18, 15, 10],
                [25, 23, 18, 15, 9],
                [25, 23, 18, 14, 12],
                [25, 23, 18, 14, 11],
                [25, 23, 18, 14, 10],
                [25, 23, 18, 14, 9],
                [25, 23, 18, 13, 12],
                [25, 23, 18, 13, 11],
                [25, 23, 18, 13, 10],
                [25, 23, 18, 13, 9],
                [25, 23, 17, 16, 12],
                [25, 23, 17, 16, 11],
                [25, 23, 17, 16, 10],
                [25, 23, 17, 16, 9],
                [25, 23, 17, 15, 12],
                [25, 23, 17, 15, 11],
                [25, 23, 17, 15, 10],
                [25, 23, 17, 15, 9],
                [25, 23, 17, 14, 12],
                [25, 23, 17, 14, 11],
                [25, 23, 17, 14, 10],
                [25, 23, 17, 14, 9],
                [25, 23, 17, 13, 12],
                [25, 23, 17, 13, 11],
                [25, 23, 17, 13, 10],
                [25, 23, 17, 13, 9],
                [25, 22, 20, 16, 12],
                [25, 22, 20, 16, 11],
                [25, 22, 20, 16, 10],
                [25, 22, 20, 16, 9],
                [25, 22, 20, 15, 12],
                [25, 22, 20, 15, 11],
                [25, 22, 20, 15, 10],
                [25, 22, 20, 15, 9],
                [25, 22, 20, 14, 12],
                [25, 22, 20, 14, 11],
                [25, 22, 20, 14, 10],
                [25, 22, 20, 14, 9],
                [25, 22, 20, 13, 12],
                [25, 22, 20, 13, 11],
                [25, 22, 20, 13, 10],
                [25, 22, 20, 13, 9],
                [25, 22, 19, 16, 12],
                [25, 22, 19, 16, 11],
                [25, 22, 19, 16, 10],
                [25, 22, 19, 16, 9],
                [25, 22, 19, 15, 12],
                [25, 22, 19, 15, 11],
                [25, 22, 19, 15, 10],
                [25, 22, 19, 15, 9],
                [25, 22, 19, 14, 12],
                [25, 22, 19, 14, 11],
                [25, 22, 19, 14, 10],
                [25, 22, 19, 14, 9],
                [25, 22, 19, 13, 12],
                [25, 22, 19, 13, 11],
                [25, 22, 19, 13, 10],
                [25, 22, 19, 13, 9],
                [25, 22, 18, 16, 12],
                [25, 22, 18, 16, 11],
                [25, 22, 18, 16, 10],
                [25, 22, 18, 16, 9],
                [25, 22, 18, 15, 12],
                [25, 22, 18, 15, 11],
                [25, 22, 18, 15, 10],
                [25, 22, 18, 15, 9],
                [25, 22, 18, 14, 12],
                [25, 22, 18, 14, 11],
                [25, 22, 18, 14, 10],
                [25, 22, 18, 14, 9],
                [25, 22, 18, 13, 12],
                [25, 22, 18, 13, 11],
                [25, 22, 18, 13, 10],
                [25, 22, 18, 13, 9],
                [25, 22, 17, 16, 12],
                [25, 22, 17, 16, 11],
                [25, 22, 17, 16, 10],
                [25, 22, 17, 16, 9],
                [25, 22, 17, 15, 12],
                [25, 22, 17, 15, 11],
                [25, 22, 17, 15, 10],
                [25, 22, 17, 15, 9],
                [25, 22, 17, 14, 12],
                [25, 22, 17, 14, 11],
                [25, 22, 17, 14, 10],
                [25, 22, 17, 14, 9],
                [25, 22, 17, 13, 12],
                [25, 22, 17, 13, 11],
                [25, 22, 17, 13, 10],
                [25, 22, 17, 13, 9],
                [25, 21, 20, 16, 12],
                [25, 21, 20, 16, 11],
                [25, 21, 20, 16, 10],
                [25, 21, 20, 16, 9],
                [25, 21, 20, 15, 12],
                [25, 21, 20, 15, 11],
                [25, 21, 20, 15, 10],
                [25, 21, 20, 15, 9],
                [25, 21, 20, 14, 12],
                [25, 21, 20, 14, 11],
                [25, 21, 20, 14, 10],
                [25, 21, 20, 14, 9],
                [25, 21, 20, 13, 12],
                [25, 21, 20, 13, 11],
                [25, 21, 20, 13, 10],
                [25, 21, 20, 13, 9],
                [25, 21, 19, 16, 12],
                [25, 21, 19, 16, 11],
                [25, 21, 19, 16, 10],
                [25, 21, 19, 16, 9],
                [25, 21, 19, 15, 12],
                [25, 21, 19, 15, 11],
                [25, 21, 19, 15, 10],
                [25, 21, 19, 15, 9],
                [25, 21, 19, 14, 12],
                [25, 21, 19, 14, 11],
                [25, 21, 19, 14, 10],
                [25, 21, 19, 14, 9],
                [25, 21, 19, 13, 12],
                [25, 21, 19, 13, 11],
                [25, 21, 19, 13, 10],
                [25, 21, 19, 13, 9],
                [25, 21, 18, 16, 12],
                [25, 21, 18, 16, 11],
                [25, 21, 18, 16, 10],
                [25, 21, 18, 16, 9],
                [25, 21, 18, 15, 12],
                [25, 21, 18, 15, 11],
                [25, 21, 18, 15, 10],
                [25, 21, 18, 15, 9],
                [25, 21, 18, 14, 12],
                [25, 21, 18, 14, 11],
                [25, 21, 18, 14, 10],
                [25, 21, 18, 14, 9],
                [25, 21, 18, 13, 12],
                [25, 21, 18, 13, 11],
                [25, 21, 18, 13, 10],
                [25, 21, 18, 13, 9],
                [25, 21, 17, 16, 12],
                [25, 21, 17, 16, 11],
                [25, 21, 17, 16, 10],
                [25, 21, 17, 16, 9],
                [25, 21, 17, 15, 12],
                [25, 21, 17, 15, 11],
                [25, 21, 17, 15, 10],
                [25, 21, 17, 15, 9],
                [25, 21, 17, 14, 12],
                [25, 21, 17, 14, 11],
                [25, 21, 17, 14, 10],
                [25, 21, 17, 14, 9],
                [25, 21, 17, 13, 12],
                [25, 21, 17, 13, 11],
                [25, 21, 17, 13, 10],
                [24, 20, 16, 12, 7],
                [24, 20, 16, 12, 6],
                [24, 20, 16, 12, 5],
                [24, 20, 16, 11, 8],
                [24, 20, 16, 11, 7],
                [24, 20, 16, 11, 6],
                [24, 20, 16, 11, 5],
                [24, 20, 16, 10, 8],
                [24, 20, 16, 10, 7],
                [24, 20, 16, 10, 6],
                [24, 20, 16, 10, 5],
                [24, 20, 16, 9, 8],
                [24, 20, 16, 9, 7],
                [24, 20, 16, 9, 6],
                [24, 20, 16, 9, 5],
                [24, 20, 15, 12, 8],
                [24, 20, 15, 12, 7],
                [24, 20, 15, 12, 6],
                [24, 20, 15, 12, 5],
                [24, 20, 15, 11, 8],
                [24, 20, 15, 11, 7],
                [24, 20, 15, 11, 6],
                [24, 20, 15, 11, 5],
                [24, 20, 15, 10, 8],
                [24, 20, 15, 10, 7],
                [24, 20, 15, 10, 6],
                [24, 20, 15, 10, 5],
                [24, 20, 15, 9, 8],
                [24, 20, 15, 9, 7],
                [24, 20, 15, 9, 6],
                [24, 20, 15, 9, 5],
                [24, 20, 14, 12, 8],
                [24, 20, 14, 12, 7],
                [24, 20, 14, 12, 6],
                [24, 20, 14, 12, 5],
                [24, 20, 14, 11, 8],
                [24, 20, 14, 11, 7],
                [24, 20, 14, 11, 6],
                [24, 20, 14, 11, 5],
                [24, 20, 14, 10, 8],
                [24, 20, 14, 10, 7],
                [24, 20, 14, 10, 6],
                [24, 20, 14, 10, 5],
                [24, 20, 14, 9, 8],
                [24, 20, 14, 9, 7],
                [24, 20, 14, 9, 6],
                [24, 20, 14, 9, 5],
                [24, 20, 13, 12, 8],
                [24, 20, 13, 12, 7],
                [24, 20, 13, 12, 6],
                [24, 20, 13, 12, 5],
                [24, 20, 13, 11, 8],
                [24, 20, 13, 11, 7],
                [24, 20, 13, 11, 6],
                [24, 20, 13, 11, 5],
                [24, 20, 13, 10, 8],
                [24, 20, 13, 10, 7],
                [24, 20, 13, 10, 6],
                [24, 20, 13, 10, 5],
                [24, 20, 13, 9, 8],
                [24, 20, 13, 9, 7],
                [24, 20, 13, 9, 6],
                [24, 20, 13, 9, 5],
                [24, 19, 16, 12, 8],
                [24, 19, 16, 12, 7],
                [24, 19, 16, 12, 6],
                [24, 19, 16, 12, 5],
                [24, 19, 16, 11, 8],
                [24, 19, 16, 11, 7],
                [24, 19, 16, 11, 6],
                [24, 19, 16, 11, 5],
                [24, 19, 16, 10, 8],
                [24, 19, 16, 10, 7],
                [24, 19, 16, 10, 6],
                [24, 19, 16, 10, 5],
                [24, 19, 16, 9, 8],
                [24, 19, 16, 9, 7],
                [24, 19, 16, 9, 6],
                [24, 19, 16, 9, 5],
                [24, 19, 15, 12, 8],
                [24, 19, 15, 12, 7],
                [24, 19, 15, 12, 6],
                [24, 19, 15, 12, 5],
                [24, 19, 15, 11, 8],
                [24, 19, 15, 11, 7],
                [24, 19, 15, 11, 6],
                [24, 19, 15, 11, 5],
                [24, 19, 15, 10, 8],
                [24, 19, 15, 10, 7],
                [24, 19, 15, 10, 6],
                [24, 19, 15, 10, 5],
                [24, 19, 15, 9, 8],
                [24, 19, 15, 9, 7],
                [24, 19, 15, 9, 6],
                [24, 19, 15, 9, 5],
                [24, 19, 14, 12, 8],
                [24, 19, 14, 12, 7],
                [24, 19, 14, 12, 6],
                [24, 19, 14, 12, 5],
                [24, 19, 14, 11, 8],
                [24, 19, 14, 11, 7],
                [24, 19, 14, 11, 6],
                [24, 19, 14, 11, 5],
                [24, 19, 14, 10, 8],
                [24, 19, 14, 10, 7],
                [24, 19, 14, 10, 6],
                [24, 19, 14, 10, 5],
                [24, 19, 14, 9, 8],
                [24, 19, 14, 9, 7],
                [24, 19, 14, 9, 6],
                [24, 19, 14, 9, 5],
                [24, 19, 13, 12, 8],
                [24, 19, 13, 12, 7],
                [24, 19, 13, 12, 6],
                [24, 19, 13, 12, 5],
                [24, 19, 13, 11, 8],
                [24, 19, 13, 11, 7],
                [24, 19, 13, 11, 6],
                [24, 19, 13, 11, 5],
                [24, 19, 13, 10, 8],
                [24, 19, 13, 10, 7],
                [24, 19, 13, 10, 6],
                [24, 19, 13, 10, 5],
                [24, 19, 13, 9, 8],
                [24, 19, 13, 9, 7],
                [24, 19, 13, 9, 6],
                [24, 19, 13, 9, 5],
                [24, 18, 16, 12, 8],
                [24, 18, 16, 12, 7],
                [24, 18, 16, 12, 6],
                [24, 18, 16, 12, 5],
                [24, 18, 16, 11, 8],
                [24, 18, 16, 11, 7],
                [24, 18, 16, 11, 6],
                [24, 18, 16, 11, 5],
                [24, 18, 16, 10, 8],
                [24, 18, 16, 10, 7],
                [24, 18, 16, 10, 6],
                [24, 18, 16, 10, 5],
                [24, 18, 16, 9, 8],
                [24, 18, 16, 9, 7],
                [24, 18, 16, 9, 6],
                [24, 18, 16, 9, 5],
                [24, 18, 15, 12, 8],
                [24, 18, 15, 12, 7],
                [24, 18, 15, 12, 6],
                [24, 18, 15, 12, 5],
                [24, 18, 15, 11, 8],
                [24, 18, 15, 11, 7],
                [24, 18, 15, 11, 6],
                [24, 18, 15, 11, 5],
                [24, 18, 15, 10, 8],
                [24, 18, 15, 10, 7],
                [24, 18, 15, 10, 6],
                [24, 18, 15, 10, 5],
                [24, 18, 15, 9, 8],
                [24, 18, 15, 9, 7],
                [24, 18, 15, 9, 6],
                [24, 18, 15, 9, 5],
                [24, 18, 14, 12, 8],
                [24, 18, 14, 12, 7],
                [24, 18, 14, 12, 6],
                [24, 18, 14, 12, 5],
                [24, 18, 14, 11, 8],
                [24, 18, 14, 11, 7],
                [24, 18, 14, 11, 6],
                [24, 18, 14, 11, 5],
                [24, 18, 14, 10, 8],
                [24, 18, 14, 10, 7],
                [24, 18, 14, 10, 6],
                [24, 18, 14, 10, 5],
                [24, 18, 14, 9, 8],
                [24, 18, 14, 9, 7],
                [24, 18, 14, 9, 6],
                [24, 18, 14, 9, 5],
                [24, 18, 13, 12, 8],
                [24, 18, 13, 12, 7],
                [24, 18, 13, 12, 6],
                [24, 18, 13, 12, 5],
                [24, 18, 13, 11, 8],
                [24, 18, 13, 11, 7],
                [24, 18, 13, 11, 6],
                [24, 18, 13, 11, 5],
                [24, 18, 13, 10, 8],
                [24, 18, 13, 10, 7],
                [24, 18, 13, 10, 6],
                [24, 18, 13, 10, 5],
                [24, 18, 13, 9, 8],
                [24, 18, 13, 9, 7],
                [24, 18, 13, 9, 6],
                [24, 18, 13, 9, 5],
                [24, 17, 16, 12, 8],
                [24, 17, 16, 12, 7],
                [24, 17, 16, 12, 6],
                [24, 17, 16, 12, 5],
                [24, 17, 16, 11, 8],
                [24, 17, 16, 11, 7],
                [24, 17, 16, 11, 6],
                [24, 17, 16, 11, 5],
                [24, 17, 16, 10, 8],
                [24, 17, 16, 10, 7],
                [24, 17, 16, 10, 6],
                [24, 17, 16, 10, 5],
                [24, 17, 16, 9, 8],
                [24, 17, 16, 9, 7],
                [24, 17, 16, 9, 6],
                [24, 17, 16, 9, 5],
                [24, 17, 15, 12, 8],
                [24, 17, 15, 12, 7],
                [24, 17, 15, 12, 6],
                [24, 17, 15, 12, 5],
                [24, 17, 15, 11, 8],
                [24, 17, 15, 11, 7],
                [24, 17, 15, 11, 6],
                [24, 17, 15, 11, 5],
                [24, 17, 15, 10, 8],
                [24, 17, 15, 10, 7],
                [24, 17, 15, 10, 6],
                [24, 17, 15, 10, 5],
                [24, 17, 15, 9, 8],
                [24, 17, 15, 9, 7],
                [24, 17, 15, 9, 6],
                [24, 17, 15, 9, 5],
                [24, 17, 14, 12, 8],
                [24, 17, 14, 12, 7],
                [24, 17, 14, 12, 6],
                [24, 17, 14, 12, 5],
                [24, 17, 14, 11, 8],
                [24, 17, 14, 11, 7],
                [24, 17, 14, 11, 6],
                [24, 17, 14, 11, 5],
                [24, 17, 14, 10, 8],
                [24, 17, 14, 10, 7],
                [24, 17, 14, 10, 6],
                [24, 17, 14, 10, 5],
                [24, 17, 14, 9, 8],
                [24, 17, 14, 9, 7],
                [24, 17, 14, 9, 6],
                [24, 17, 14, 9, 5],
                [24, 17, 13, 12, 8],
                [24, 17, 13, 12, 7],
                [24, 17, 13, 12, 6],
                [24, 17, 13, 12, 5],
                [24, 17, 13, 11, 8],
                [24, 17, 13, 11, 7],
                [24, 17, 13, 11, 6],
                [24, 17, 13, 11, 5],
                [24, 17, 13, 10, 8],
                [24, 17, 13, 10, 7],
                [24, 17, 13, 10, 6],
                [24, 17, 13, 10, 5],
                [24, 17, 13, 9, 8],
                [24, 17, 13, 9, 7],
                [24, 17, 13, 9, 6],
                [24, 17, 13, 9, 5],
                [23, 20, 16, 12, 8],
                [23, 20, 16, 12, 7],
                [23, 20, 16, 12, 6],
                [23, 20, 16, 12, 5],
                [23, 20, 16, 11, 8],
                [23, 20, 16, 11, 7],
                [23, 20, 16, 11, 6],
                [23, 20, 16, 11, 5],
                [23, 20, 16, 10, 8],
                [23, 20, 16, 10, 7],
                [23, 20, 16, 10, 6],
                [23, 20, 16, 10, 5],
                [23, 20, 16, 9, 8],
                [23, 20, 16, 9, 7],
                [23, 20, 16, 9, 6],
                [23, 20, 16, 9, 5],
                [23, 20, 15, 12, 8],
                [23, 20, 15, 12, 7],
                [23, 20, 15, 12, 6],
                [23, 20, 15, 12, 5],
                [23, 20, 15, 11, 8],
                [23, 20, 15, 11, 7],
                [23, 20, 15, 11, 6],
                [23, 20, 15, 11, 5],
                [23, 20, 15, 10, 8],
                [23, 20, 15, 10, 7],
                [23, 20, 15, 10, 6],
                [23, 20, 15, 10, 5],
                [23, 20, 15, 9, 8],
                [23, 20, 15, 9, 7],
                [23, 20, 15, 9, 6],
                [23, 20, 15, 9, 5],
                [23, 20, 14, 12, 8],
                [23, 20, 14, 12, 7],
                [23, 20, 14, 12, 6],
                [23, 20, 14, 12, 5],
                [23, 20, 14, 11, 8],
                [23, 20, 14, 11, 7],
                [23, 20, 14, 11, 6],
                [23, 20, 14, 11, 5],
                [23, 20, 14, 10, 8],
                [23, 20, 14, 10, 7],
                [23, 20, 14, 10, 6],
                [23, 20, 14, 10, 5],
                [23, 20, 14, 9, 8],
                [23, 20, 14, 9, 7],
                [23, 20, 14, 9, 6],
                [23, 20, 14, 9, 5],
                [23, 20, 13, 12, 8],
                [23, 20, 13, 12, 7],
                [23, 20, 13, 12, 6],
                [23, 20, 13, 12, 5],
                [23, 20, 13, 11, 8],
                [23, 20, 13, 11, 7],
                [23, 20, 13, 11, 6],
                [23, 20, 13, 11, 5],
                [23, 20, 13, 10, 8],
                [23, 20, 13, 10, 7],
                [23, 20, 13, 10, 6],
                [23, 20, 13, 10, 5],
                [23, 20, 13, 9, 8],
                [23, 20, 13, 9, 7],
                [23, 20, 13, 9, 6],
                [23, 20, 13, 9, 5],
                [23, 19, 16, 12, 8],
                [23, 19, 16, 12, 7],
                [23, 19, 16, 12, 6],
                [23, 19, 16, 12, 5],
                [23, 19, 16, 11, 8],
                [23, 19, 16, 11, 7],
                [23, 19, 16, 11, 6],
                [23, 19, 16, 11, 5],
                [23, 19, 16, 10, 8],
                [23, 19, 16, 10, 7],
                [23, 19, 16, 10, 6],
                [23, 19, 16, 10, 5],
                [23, 19, 16, 9, 8],
                [23, 19, 16, 9, 7],
                [23, 19, 16, 9, 6],
                [23, 19, 16, 9, 5],
                [23, 19, 15, 12, 8],
                [23, 19, 15, 12, 7],
                [23, 19, 15, 12, 6],
                [23, 19, 15, 12, 5],
                [23, 19, 15, 11, 8],
                [23, 19, 15, 11, 6],
                [23, 19, 15, 11, 5],
                [23, 19, 15, 10, 8],
                [23, 19, 15, 10, 7],
                [23, 19, 15, 10, 6],
                [23, 19, 15, 10, 5],
                [23, 19, 15, 9, 8],
                [23, 19, 15, 9, 7],
                [23, 19, 15, 9, 6],
                [23, 19, 15, 9, 5],
                [23, 19, 14, 12, 8],
                [23, 19, 14, 12, 7],
                [23, 19, 14, 12, 6],
                [23, 19, 14, 12, 5],
                [23, 19, 14, 11, 8],
                [23, 19, 14, 11, 7],
                [23, 19, 14, 11, 6],
                [23, 19, 14, 11, 5],
                [23, 19, 14, 10, 8],
                [23, 19, 14, 10, 7],
                [23, 19, 14, 10, 6],
                [23, 19, 14, 10, 5],
                [23, 19, 14, 9, 8],
                [23, 19, 14, 9, 7],
                [23, 19, 14, 9, 6],
                [23, 19, 14, 9, 5],
                [23, 19, 13, 12, 8],
                [23, 19, 13, 12, 7],
                [23, 19, 13, 12, 6],
                [23, 19, 13, 12, 5],
                [23, 19, 13, 11, 8],
                [23, 19, 13, 11, 7],
                [23, 19, 13, 11, 6],
                [23, 19, 13, 11, 5],
                [23, 19, 13, 10, 8],
                [23, 19, 13, 10, 7],
                [23, 19, 13, 10, 6],
                [23, 19, 13, 10, 5],
                [23, 19, 13, 9, 8],
                [23, 19, 13, 9, 7],
                [23, 19, 13, 9, 6],
                [23, 19, 13, 9, 5],
                [23, 18, 16, 12, 8],
                [23, 18, 16, 12, 7],
                [23, 18, 16, 12, 6],
                [23, 18, 16, 12, 5],
                [23, 18, 16, 11, 8],
                [23, 18, 16, 11, 7],
                [23, 18, 16, 11, 6],
                [23, 18, 16, 11, 5],
                [23, 18, 16, 10, 8],
                [23, 18, 16, 10, 7],
                [23, 18, 16, 10, 6],
                [23, 18, 16, 10, 5],
                [23, 18, 16, 9, 8],
                [23, 18, 16, 9, 7],
                [23, 18, 16, 9, 6],
                [23, 18, 16, 9, 5],
                [23, 18, 15, 12, 8],
                [23, 18, 15, 12, 7],
                [23, 18, 15, 12, 6],
                [23, 18, 15, 12, 5],
                [23, 18, 15, 11, 8],
                [23, 18, 15, 11, 7],
                [23, 18, 15, 11, 6],
                [23, 18, 15, 11, 5],
                [23, 18, 15, 10, 8],
                [23, 18, 15, 10, 7],
                [23, 18, 15, 10, 6],
                [23, 18, 15, 10, 5],
                [23, 18, 15, 9, 8],
                [23, 18, 15, 9, 7],
                [23, 18, 15, 9, 6],
                [23, 18, 15, 9, 5],
                [23, 18, 14, 12, 8],
                [23, 18, 14, 12, 7],
                [23, 18, 14, 12, 6],
                [23, 18, 14, 12, 5],
                [23, 18, 14, 11, 8],
                [23, 18, 14, 11, 7],
                [23, 18, 14, 11, 6],
                [23, 18, 14, 11, 5],
                [23, 18, 14, 10, 8],
                [23, 18, 14, 10, 7],
                [23, 18, 14, 10, 6],
                [23, 18, 14, 10, 5],
                [23, 18, 14, 9, 8],
                [23, 18, 14, 9, 7],
                [23, 18, 14, 9, 6],
                [23, 18, 14, 9, 5],
                [23, 18, 13, 12, 8],
                [23, 18, 13, 12, 7],
                [23, 18, 13, 12, 6],
                [23, 18, 13, 12, 5],
                [23, 18, 13, 11, 8],
                [23, 18, 13, 11, 7],
                [23, 18, 13, 11, 6],
                [23, 18, 13, 11, 5],
                [23, 18, 13, 10, 8],
                [23, 18, 13, 10, 7],
                [23, 18, 13, 10, 6],
                [23, 18, 13, 10, 5],
                [23, 18, 13, 9, 8],
                [23, 18, 13, 9, 7],
                [23, 18, 13, 9, 6],
                [23, 18, 13, 9, 5],
                [23, 17, 16, 12, 8],
                [23, 17, 16, 12, 7],
                [23, 17, 16, 12, 6],
                [23, 17, 16, 12, 5],
                [23, 17, 16, 11, 8],
                [23, 17, 16, 11, 7],
                [23, 17, 16, 11, 6],
                [23, 17, 16, 11, 5],
                [23, 17, 16, 10, 8],
                [23, 17, 16, 10, 7],
                [23, 17, 16, 10, 6],
                [23, 17, 16, 10, 5],
                [23, 17, 16, 9, 8],
                [23, 17, 16, 9, 7],
                [23, 17, 16, 9, 6],
                [23, 17, 16, 9, 5],
                [23, 17, 15, 12, 8],
                [23, 17, 15, 12, 7],
                [23, 17, 15, 12, 6],
                [23, 17, 15, 12, 5],
                [23, 17, 15, 11, 8],
                [23, 17, 15, 11, 7],
                [23, 17, 15, 11, 6],
                [23, 17, 15, 11, 5],
                [23, 17, 15, 10, 8],
                [23, 17, 15, 10, 7],
                [23, 17, 15, 10, 6],
                [23, 17, 15, 10, 5],
                [23, 17, 15, 9, 8],
                [23, 17, 15, 9, 7],
                [23, 17, 15, 9, 6],
                [23, 17, 15, 9, 5],
                [23, 17, 14, 12, 8],
                [23, 17, 14, 12, 7],
                [23, 17, 14, 12, 6],
                [23, 17, 14, 12, 5],
                [23, 17, 14, 11, 8],
                [23, 17, 14, 11, 7],
                [23, 17, 14, 11, 6],
                [23, 17, 14, 11, 5],
                [23, 17, 14, 10, 8],
                [23, 17, 14, 10, 7],
                [23, 17, 14, 10, 6],
                [23, 17, 14, 10, 5],
                [23, 17, 14, 9, 8],
                [23, 17, 14, 9, 7],
                [23, 17, 14, 9, 6],
                [23, 17, 14, 9, 5],
                [23, 17, 13, 12, 8],
                [23, 17, 13, 12, 7],
                [23, 17, 13, 12, 6],
                [23, 17, 13, 12, 5],
                [23, 17, 13, 11, 8],
                [23, 17, 13, 11, 7],
                [23, 17, 13, 11, 6],
                [23, 17, 13, 11, 5],
                [23, 17, 13, 10, 8],
                [23, 17, 13, 10, 7],
                [23, 17, 13, 10, 6],
                [23, 17, 13, 10, 5],
                [23, 17, 13, 9, 8],
                [23, 17, 13, 9, 7],
                [23, 17, 13, 9, 6],
                [23, 17, 13, 9, 5],
                [22, 20, 16, 12, 8],
                [22, 20, 16, 12, 7],
                [22, 20, 16, 12, 6],
                [22, 20, 16, 12, 5],
                [22, 20, 16, 11, 8],
                [22, 20, 16, 11, 7],
                [22, 20, 16, 11, 6],
                [22, 20, 16, 11, 5],
                [22, 20, 16, 10, 8],
                [22, 20, 16, 10, 7],
                [22, 20, 16, 10, 6],
                [22, 20, 16, 10, 5],
                [22, 20, 16, 9, 8],
                [22, 20, 16, 9, 7],
                [22, 20, 16, 9, 6],
                [22, 20, 16, 9, 5],
                [22, 20, 15, 12, 8],
                [22, 20, 15, 12, 7],
                [22, 20, 15, 12, 6],
                [22, 20, 15, 12, 5],
                [22, 20, 15, 11, 8],
                [22, 20, 15, 11, 7],
                [22, 20, 15, 11, 6],
                [22, 20, 15, 11, 5],
                [22, 20, 15, 10, 8],
                [22, 20, 15, 10, 7],
                [22, 20, 15, 10, 6],
                [22, 20, 15, 10, 5],
                [22, 20, 15, 9, 8],
                [22, 20, 15, 9, 7],
                [22, 20, 15, 9, 6],
                [22, 20, 15, 9, 5],
                [22, 20, 14, 12, 8],
                [22, 20, 14, 12, 7],
                [22, 20, 14, 12, 6],
                [22, 20, 14, 12, 5],
                [22, 20, 14, 11, 8],
                [22, 20, 14, 11, 7],
                [22, 20, 14, 11, 6],
                [22, 20, 14, 11, 5],
                [22, 20, 14, 10, 8],
                [22, 20, 14, 10, 7],
                [22, 20, 14, 10, 6],
                [22, 20, 14, 10, 5],
                [22, 20, 14, 9, 8],
                [22, 20, 14, 9, 7],
                [22, 20, 14, 9, 6],
                [22, 20, 14, 9, 5],
                [22, 20, 13, 12, 8],
                [22, 20, 13, 12, 7],
                [22, 20, 13, 12, 6],
                [22, 20, 13, 12, 5],
                [22, 20, 13, 11, 8],
                [22, 20, 13, 11, 7],
                [22, 20, 13, 11, 6],
                [22, 20, 13, 11, 5],
                [22, 20, 13, 10, 8],
                [22, 20, 13, 10, 7],
                [22, 20, 13, 10, 6],
                [22, 20, 13, 10, 5],
                [22, 20, 13, 9, 8],
                [22, 20, 13, 9, 7],
                [22, 20, 13, 9, 6],
                [22, 20, 13, 9, 5],
                [22, 19, 16, 12, 8],
                [22, 19, 16, 12, 7],
                [22, 19, 16, 12, 6],
                [22, 19, 16, 12, 5],
                [22, 19, 16, 11, 8],
                [22, 19, 16, 11, 7],
                [22, 19, 16, 11, 6],
                [22, 19, 16, 11, 5],
                [22, 19, 16, 10, 8],
                [22, 19, 16, 10, 7],
                [22, 19, 16, 10, 6],
                [22, 19, 16, 10, 5],
                [22, 19, 16, 9, 8],
                [22, 19, 16, 9, 7],
                [22, 19, 16, 9, 6],
                [22, 19, 16, 9, 5],
                [22, 19, 15, 12, 8],
                [22, 19, 15, 12, 7],
                [22, 19, 15, 12, 6],
                [22, 19, 15, 12, 5],
                [22, 19, 15, 11, 8],
                [22, 19, 15, 11, 7],
                [22, 19, 15, 11, 6],
                [22, 19, 15, 11, 5],
                [22, 19, 15, 10, 8],
                [22, 19, 15, 10, 7],
                [22, 19, 15, 10, 6],
                [22, 19, 15, 10, 5],
                [22, 19, 15, 9, 8],
                [22, 19, 15, 9, 7],
                [22, 19, 15, 9, 6],
                [22, 19, 15, 9, 5],
                [22, 19, 14, 12, 8],
                [22, 19, 14, 12, 7],
                [22, 19, 14, 12, 6],
                [22, 19, 14, 12, 5],
                [22, 19, 14, 11, 8],
                [22, 19, 14, 11, 7],
                [22, 19, 14, 11, 6],
                [22, 19, 14, 11, 5],
                [22, 19, 14, 10, 8],
                [22, 19, 14, 10, 7],
                [22, 19, 14, 10, 6],
                [22, 19, 14, 10, 5],
                [22, 19, 14, 9, 8],
                [22, 19, 14, 9, 7],
                [22, 19, 14, 9, 6],
                [22, 19, 14, 9, 5],
                [22, 19, 13, 12, 8],
                [22, 19, 13, 12, 7],
                [22, 19, 13, 12, 6],
                [22, 19, 13, 12, 5],
                [22, 19, 13, 11, 8],
                [22, 19, 13, 11, 7],
                [22, 19, 13, 11, 6],
                [22, 19, 13, 11, 5],
                [22, 19, 13, 10, 8],
                [22, 19, 13, 10, 7],
                [22, 19, 13, 10, 6],
                [22, 19, 13, 10, 5],
                [22, 19, 13, 9, 8],
                [22, 19, 13, 9, 7],
                [22, 19, 13, 9, 6],
                [22, 19, 13, 9, 5],
                [22, 18, 16, 12, 8],
                [22, 18, 16, 12, 7],
                [22, 18, 16, 12, 6],
                [22, 18, 16, 12, 5],
                [22, 18, 16, 11, 8],
                [22, 18, 16, 11, 7],
                [22, 18, 16, 11, 6],
                [22, 18, 16, 11, 5],
                [22, 18, 16, 10, 8],
                [22, 18, 16, 10, 7],
                [22, 18, 16, 10, 6],
                [22, 18, 16, 10, 5],
                [22, 18, 16, 9, 8],
                [22, 18, 16, 9, 7],
                [22, 18, 16, 9, 6],
                [22, 18, 16, 9, 5],
                [22, 18, 15, 12, 8],
                [22, 18, 15, 12, 7],
                [22, 18, 15, 12, 6],
                [22, 18, 15, 12, 5],
                [22, 18, 15, 11, 8],
                [22, 18, 15, 11, 7],
                [22, 18, 15, 11, 6],
                [22, 18, 15, 11, 5],
                [22, 18, 15, 10, 8],
                [22, 18, 15, 10, 7],
                [22, 18, 15, 10, 6],
                [22, 18, 15, 10, 5],
                [22, 18, 15, 9, 8],
                [22, 18, 15, 9, 7],
                [22, 18, 15, 9, 6],
                [22, 18, 15, 9, 5],
                [22, 18, 14, 12, 8],
                [22, 18, 14, 12, 7],
                [22, 18, 14, 12, 6],
                [22, 18, 14, 12, 5],
                [22, 18, 14, 11, 8],
                [22, 18, 14, 11, 7],
                [22, 18, 14, 11, 6],
                [22, 18, 14, 11, 5],
                [22, 18, 14, 10, 8],
                [22, 18, 14, 10, 7],
                [22, 18, 14, 10, 5],
                [22, 18, 14, 9, 8],
                [22, 18, 14, 9, 7],
                [22, 18, 14, 9, 6],
                [22, 18, 14, 9, 5],
                [22, 18, 13, 12, 8],
                [22, 18, 13, 12, 7],
                [22, 18, 13, 12, 6],
                [22, 18, 13, 12, 5],
                [22, 18, 13, 11, 8],
                [22, 18, 13, 11, 7],
                [22, 18, 13, 11, 6],
                [22, 18, 13, 11, 5],
                [22, 18, 13, 10, 8],
                [22, 18, 13, 10, 7],
                [22, 18, 13, 10, 6],
                [22, 18, 13, 10, 5],
                [22, 18, 13, 9, 8],
                [22, 18, 13, 9, 7],
                [22, 18, 13, 9, 6],
                [22, 18, 13, 9, 5],
                [22, 17, 16, 12, 8],
                [22, 17, 16, 12, 7],
                [22, 17, 16, 12, 6],
                [22, 17, 16, 12, 5],
                [22, 17, 16, 11, 8],
                [22, 17, 16, 11, 7],
                [22, 17, 16, 11, 6],
                [22, 17, 16, 11, 5],
                [22, 17, 16, 10, 8],
                [22, 17, 16, 10, 7],
                [22, 17, 16, 10, 6],
                [22, 17, 16, 10, 5],
                [22, 17, 16, 9, 8],
                [22, 17, 16, 9, 7],
                [22, 17, 16, 9, 6],
                [22, 17, 16, 9, 5],
                [22, 17, 15, 12, 8],
                [22, 17, 15, 12, 7],
                [22, 17, 15, 12, 6],
                [22, 17, 15, 12, 5],
                [22, 17, 15, 11, 8],
                [22, 17, 15, 11, 7],
                [22, 17, 15, 11, 6],
                [22, 17, 15, 11, 5],
                [22, 17, 15, 10, 8],
                [22, 17, 15, 10, 7],
                [22, 17, 15, 10, 6],
                [22, 17, 15, 10, 5],
                [22, 17, 15, 9, 8],
                [22, 17, 15, 9, 7],
                [22, 17, 15, 9, 6],
                [22, 17, 15, 9, 5],
                [22, 17, 14, 12, 8],
                [22, 17, 14, 12, 7],
                [22, 17, 14, 12, 6],
                [22, 17, 14, 12, 5],
                [22, 17, 14, 11, 8],
                [22, 17, 14, 11, 7],
                [22, 17, 14, 11, 6],
                [22, 17, 14, 11, 5],
                [22, 17, 14, 10, 8],
                [22, 17, 14, 10, 7],
                [22, 17, 14, 10, 6],
                [22, 17, 14, 10, 5],
                [22, 17, 14, 9, 8],
                [22, 17, 14, 9, 7],
                [22, 17, 14, 9, 6],
                [22, 17, 14, 9, 5],
                [22, 17, 13, 12, 8],
                [22, 17, 13, 12, 7],
                [22, 17, 13, 12, 6],
                [22, 17, 13, 12, 5],
                [22, 17, 13, 11, 8],
                [22, 17, 13, 11, 7],
                [22, 17, 13, 11, 6],
                [22, 17, 13, 11, 5],
                [22, 17, 13, 10, 8],
                [22, 17, 13, 10, 7],
                [22, 17, 13, 10, 6],
                [22, 17, 13, 10, 5],
                [22, 17, 13, 9, 8],
                [22, 17, 13, 9, 7],
                [22, 17, 13, 9, 6],
                [22, 17, 13, 9, 5],
                [21, 20, 16, 12, 8],
                [21, 20, 16, 12, 7],
                [21, 20, 16, 12, 6],
                [21, 20, 16, 12, 5],
                [21, 20, 16, 11, 8],
                [21, 20, 16, 11, 7],
                [21, 20, 16, 11, 6],
                [21, 20, 16, 11, 5],
                [21, 20, 16, 10, 8],
                [21, 20, 16, 10, 7],
                [21, 20, 16, 10, 6],
                [21, 20, 16, 10, 5],
                [21, 20, 16, 9, 8],
                [21, 20, 16, 9, 7],
                [21, 20, 16, 9, 6],
                [21, 20, 16, 9, 5],
                [21, 20, 15, 12, 8],
                [21, 20, 15, 12, 7],
                [21, 20, 15, 12, 6],
                [21, 20, 15, 12, 5],
                [21, 20, 15, 11, 8],
                [21, 20, 15, 11, 7],
                [21, 20, 15, 11, 6],
                [21, 20, 15, 11, 5],
                [21, 20, 15, 10, 8],
                [21, 20, 15, 10, 7],
                [21, 20, 15, 10, 6],
                [21, 20, 15, 10, 5],
                [21, 20, 15, 9, 8],
                [21, 20, 15, 9, 7],
                [21, 20, 15, 9, 6],
                [21, 20, 15, 9, 5],
                [21, 20, 14, 12, 8],
                [21, 20, 14, 12, 7],
                [21, 20, 14, 12, 6],
                [21, 20, 14, 12, 5],
                [21, 20, 14, 11, 8],
                [21, 20, 14, 11, 7],
                [21, 20, 14, 11, 6],
                [21, 20, 14, 11, 5],
                [21, 20, 14, 10, 8],
                [21, 20, 14, 10, 7],
                [21, 20, 14, 10, 6],
                [21, 20, 14, 10, 5],
                [21, 20, 14, 9, 8],
                [21, 20, 14, 9, 7],
                [21, 20, 14, 9, 6],
                [21, 20, 14, 9, 5],
                [21, 20, 13, 12, 8],
                [21, 20, 13, 12, 7],
                [21, 20, 13, 12, 6],
                [21, 20, 13, 12, 5],
                [21, 20, 13, 11, 8],
                [21, 20, 13, 11, 7],
                [21, 20, 13, 11, 6],
                [21, 20, 13, 11, 5],
                [21, 20, 13, 10, 8],
                [21, 20, 13, 10, 7],
                [21, 20, 13, 10, 6],
                [21, 20, 13, 10, 5],
                [21, 20, 13, 9, 8],
                [21, 20, 13, 9, 7],
                [21, 20, 13, 9, 6],
                [21, 20, 13, 9, 5],
                [21, 19, 16, 12, 8],
                [21, 19, 16, 12, 7],
                [21, 19, 16, 12, 6],
                [21, 19, 16, 12, 5],
                [21, 19, 16, 11, 8],
                [21, 19, 16, 11, 7],
                [21, 19, 16, 11, 6],
                [21, 19, 16, 11, 5],
                [21, 19, 16, 10, 8],
                [21, 19, 16, 10, 7],
                [21, 19, 16, 10, 6],
                [21, 19, 16, 10, 5],
                [21, 19, 16, 9, 8],
                [21, 19, 16, 9, 7],
                [21, 19, 16, 9, 6],
                [21, 19, 16, 9, 5],
                [21, 19, 15, 12, 8],
                [21, 19, 15, 12, 7],
                [21, 19, 15, 12, 6],
                [21, 19, 15, 12, 5],
                [21, 19, 15, 11, 8],
                [21, 19, 15, 11, 7],
                [21, 19, 15, 11, 6],
                [21, 19, 15, 11, 5],
                [21, 19, 15, 10, 8],
                [21, 19, 15, 10, 7],
                [21, 19, 15, 10, 6],
                [21, 19, 15, 10, 5],
                [21, 19, 15, 9, 8],
                [21, 19, 15, 9, 7],
                [21, 19, 15, 9, 6],
                [21, 19, 15, 9, 5],
                [21, 19, 14, 12, 8],
                [21, 19, 14, 12, 7],
                [21, 19, 14, 12, 6],
                [21, 19, 14, 12, 5],
                [21, 19, 14, 11, 8],
                [21, 19, 14, 11, 7],
                [21, 19, 14, 11, 6],
                [21, 19, 14, 11, 5],
                [21, 19, 14, 10, 8],
                [21, 19, 14, 10, 7],
                [21, 19, 14, 10, 6],
                [21, 19, 14, 10, 5],
                [21, 19, 14, 9, 8],
                [21, 19, 14, 9, 7],
                [21, 19, 14, 9, 6],
                [21, 19, 14, 9, 5],
                [21, 19, 13, 12, 8],
                [21, 19, 13, 12, 7],
                [21, 19, 13, 12, 6],
                [21, 19, 13, 12, 5],
                [21, 19, 13, 11, 8],
                [21, 19, 13, 11, 7],
                [21, 19, 13, 11, 6],
                [21, 19, 13, 11, 5],
                [21, 19, 13, 10, 8],
                [21, 19, 13, 10, 7],
                [21, 19, 13, 10, 6],
                [21, 19, 13, 10, 5],
                [21, 19, 13, 9, 8],
                [21, 19, 13, 9, 7],
                [21, 19, 13, 9, 6],
                [21, 19, 13, 9, 5],
                [21, 18, 16, 12, 8],
                [21, 18, 16, 12, 7],
                [21, 18, 16, 12, 6],
                [21, 18, 16, 12, 5],
                [21, 18, 16, 11, 8],
                [21, 18, 16, 11, 7],
                [21, 18, 16, 11, 6],
                [21, 18, 16, 11, 5],
                [21, 18, 16, 10, 8],
                [21, 18, 16, 10, 7],
                [21, 18, 16, 10, 6],
                [21, 18, 16, 10, 5],
                [21, 18, 16, 9, 8],
                [21, 18, 16, 9, 7],
                [21, 18, 16, 9, 6],
                [21, 18, 16, 9, 5],
                [21, 18, 15, 12, 8],
                [21, 18, 15, 12, 7],
                [21, 18, 15, 12, 6],
                [21, 18, 15, 12, 5],
                [21, 18, 15, 11, 8],
                [21, 18, 15, 11, 7],
                [21, 18, 15, 11, 6],
                [21, 18, 15, 11, 5],
                [21, 18, 15, 10, 8],
                [21, 18, 15, 10, 7],
                [21, 18, 15, 10, 6],
                [21, 18, 15, 10, 5],
                [21, 18, 15, 9, 8],
                [21, 18, 15, 9, 7],
                [21, 18, 15, 9, 6],
                [21, 18, 15, 9, 5],
                [21, 18, 14, 12, 8],
                [21, 18, 14, 12, 7],
                [21, 18, 14, 12, 6],
                [21, 18, 14, 12, 5],
                [21, 18, 14, 11, 8],
                [21, 18, 14, 11, 7],
                [21, 18, 14, 11, 6],
                [21, 18, 14, 11, 5],
                [21, 18, 14, 10, 8],
                [21, 18, 14, 10, 7],
                [21, 18, 14, 10, 6],
                [21, 18, 14, 10, 5],
                [21, 18, 14, 9, 8],
                [21, 18, 14, 9, 7],
                [21, 18, 14, 9, 6],
                [21, 18, 14, 9, 5],
                [21, 18, 13, 12, 8],
                [21, 18, 13, 12, 7],
                [21, 18, 13, 12, 6],
                [21, 18, 13, 12, 5],
                [21, 18, 13, 11, 8],
                [21, 18, 13, 11, 7],
                [21, 18, 13, 11, 6],
                [21, 18, 13, 11, 5],
                [21, 18, 13, 10, 8],
                [21, 18, 13, 10, 7],
                [21, 18, 13, 10, 6],
                [21, 18, 13, 10, 5],
                [21, 18, 13, 9, 8],
                [21, 18, 13, 9, 7],
                [21, 18, 13, 9, 6],
                [21, 18, 13, 9, 5],
                [21, 17, 16, 12, 8],
                [21, 17, 16, 12, 7],
                [21, 17, 16, 12, 6],
                [21, 17, 16, 12, 5],
                [21, 17, 16, 11, 8],
                [21, 17, 16, 11, 7],
                [21, 17, 16, 11, 6],
                [21, 17, 16, 11, 5],
                [21, 17, 16, 10, 8],
                [21, 17, 16, 10, 7],
                [21, 17, 16, 10, 6],
                [21, 17, 16, 10, 5],
                [21, 17, 16, 9, 8],
                [21, 17, 16, 9, 7],
                [21, 17, 16, 9, 6],
                [21, 17, 16, 9, 5],
                [21, 17, 15, 12, 8],
                [21, 17, 15, 12, 7],
                [21, 17, 15, 12, 6],
                [21, 17, 15, 12, 5],
                [21, 17, 15, 11, 8],
                [21, 17, 15, 11, 7],
                [21, 17, 15, 11, 6],
                [21, 17, 15, 11, 5],
                [21, 17, 15, 10, 8],
                [21, 17, 15, 10, 7],
                [21, 17, 15, 10, 6],
                [21, 17, 15, 10, 5],
                [21, 17, 15, 9, 8],
                [21, 17, 15, 9, 7],
                [21, 17, 15, 9, 6],
                [21, 17, 15, 9, 5],
                [21, 17, 14, 12, 8],
                [21, 17, 14, 12, 7],
                [21, 17, 14, 12, 6],
                [21, 17, 14, 12, 5],
                [21, 17, 14, 11, 8],
                [21, 17, 14, 11, 7],
                [21, 17, 14, 11, 6],
                [21, 17, 14, 11, 5],
                [21, 17, 14, 10, 8],
                [21, 17, 14, 10, 7],
                [21, 17, 14, 10, 6],
                [21, 17, 14, 10, 5],
                [21, 17, 14, 9, 8],
                [21, 17, 14, 9, 7],
                [21, 17, 14, 9, 6],
                [21, 17, 14, 9, 5],
                [21, 17, 13, 12, 8],
                [21, 17, 13, 12, 7],
                [21, 17, 13, 12, 6],
                [21, 17, 13, 12, 5],
                [21, 17, 13, 11, 8],
                [21, 17, 13, 11, 7],
                [21, 17, 13, 11, 6],
                [21, 17, 13, 11, 5],
                [21, 17, 13, 10, 8],
                [21, 17, 13, 10, 7],
                [21, 17, 13, 10, 6],
                [21, 17, 13, 10, 5],
                [21, 17, 13, 9, 8],
                [21, 17, 13, 9, 7],
                [21, 17, 13, 9, 6],
                [20, 16, 12, 8, 3],
                [20, 16, 12, 8, 2],
                [20, 16, 12, 8, 1],
                [20, 16, 12, 7, 4],
                [20, 16, 12, 7, 3],
                [20, 16, 12, 7, 2],
                [20, 16, 12, 7, 1],
                [20, 16, 12, 6, 4],
                [20, 16, 12, 6, 3],
                [20, 16, 12, 6, 2],
                [20, 16, 12, 6, 1],
                [20, 16, 12, 5, 4],
                [20, 16, 12, 5, 3],
                [20, 16, 12, 5, 2],
                [20, 16, 12, 5, 1],
                [20, 16, 11, 8, 4],
                [20, 16, 11, 8, 3],
                [20, 16, 11, 8, 2],
                [20, 16, 11, 8, 1],
                [20, 16, 11, 7, 4],
                [20, 16, 11, 7, 3],
                [20, 16, 11, 7, 2],
                [20, 16, 11, 7, 1],
                [20, 16, 11, 6, 4],
                [20, 16, 11, 6, 3],
                [20, 16, 11, 6, 2],
                [20, 16, 11, 6, 1],
                [20, 16, 11, 5, 4],
                [20, 16, 11, 5, 3],
                [20, 16, 11, 5, 2],
                [20, 16, 11, 5, 1],
                [20, 16, 10, 8, 4],
                [20, 16, 10, 8, 3],
                [20, 16, 10, 8, 2],
                [20, 16, 10, 8, 1],
                [20, 16, 10, 7, 4],
                [20, 16, 10, 7, 3],
                [20, 16, 10, 7, 2],
                [20, 16, 10, 7, 1],
                [20, 16, 10, 6, 4],
                [20, 16, 10, 6, 3],
                [20, 16, 10, 6, 2],
                [20, 16, 10, 6, 1],
                [20, 16, 10, 5, 4],
                [20, 16, 10, 5, 3],
                [20, 16, 10, 5, 2],
                [20, 16, 10, 5, 1],
                [20, 16, 9, 8, 4],
                [20, 16, 9, 8, 3],
                [20, 16, 9, 8, 2],
                [20, 16, 9, 8, 1],
                [20, 16, 9, 7, 4],
                [20, 16, 9, 7, 3],
                [20, 16, 9, 7, 2],
                [20, 16, 9, 7, 1],
                [20, 16, 9, 6, 4],
                [20, 16, 9, 6, 3],
                [20, 16, 9, 6, 2],
                [20, 16, 9, 6, 1],
                [20, 16, 9, 5, 4],
                [20, 16, 9, 5, 3],
                [20, 16, 9, 5, 2],
                [20, 16, 9, 5, 1],
                [20, 15, 12, 8, 4],
                [20, 15, 12, 8, 3],
                [20, 15, 12, 8, 2],
                [20, 15, 12, 8, 1],
                [20, 15, 12, 7, 4],
                [20, 15, 12, 7, 3],
                [20, 15, 12, 7, 2],
                [20, 15, 12, 7, 1],
                [20, 15, 12, 6, 4],
                [20, 15, 12, 6, 3],
                [20, 15, 12, 6, 2],
                [20, 15, 12, 6, 1],
                [20, 15, 12, 5, 4],
                [20, 15, 12, 5, 3],
                [20, 15, 12, 5, 2],
                [20, 15, 12, 5, 1],
                [20, 15, 11, 8, 4],
                [20, 15, 11, 8, 3],
                [20, 15, 11, 8, 2],
                [20, 15, 11, 8, 1],
                [20, 15, 11, 7, 4],
                [20, 15, 11, 7, 3],
                [20, 15, 11, 7, 2],
                [20, 15, 11, 7, 1],
                [20, 15, 11, 6, 4],
                [20, 15, 11, 6, 3],
                [20, 15, 11, 6, 2],
                [20, 15, 11, 6, 1],
                [20, 15, 11, 5, 4],
                [20, 15, 11, 5, 3],
                [20, 15, 11, 5, 2],
                [20, 15, 11, 5, 1],
                [20, 15, 10, 8, 4],
                [20, 15, 10, 8, 3],
                [20, 15, 10, 8, 2],
                [20, 15, 10, 8, 1],
                [20, 15, 10, 7, 4],
                [20, 15, 10, 7, 3],
                [20, 15, 10, 7, 2],
                [20, 15, 10, 7, 1],
                [20, 15, 10, 6, 4],
                [20, 15, 10, 6, 3],
                [20, 15, 10, 6, 2],
                [20, 15, 10, 6, 1],
                [20, 15, 10, 5, 4],
                [20, 15, 10, 5, 3],
                [20, 15, 10, 5, 2],
                [20, 15, 10, 5, 1],
                [20, 15, 9, 8, 4],
                [20, 15, 9, 8, 3],
                [20, 15, 9, 8, 2],
                [20, 15, 9, 8, 1],
                [20, 15, 9, 7, 4],
                [20, 15, 9, 7, 3],
                [20, 15, 9, 7, 2],
                [20, 15, 9, 7, 1],
                [20, 15, 9, 6, 4],
                [20, 15, 9, 6, 3],
                [20, 15, 9, 6, 2],
                [20, 15, 9, 6, 1],
                [20, 15, 9, 5, 4],
                [20, 15, 9, 5, 3],
                [20, 15, 9, 5, 2],
                [20, 15, 9, 5, 1],
                [20, 14, 12, 8, 4],
                [20, 14, 12, 8, 3],
                [20, 14, 12, 8, 2],
                [20, 14, 12, 8, 1],
                [20, 14, 12, 7, 4],
                [20, 14, 12, 7, 3],
                [20, 14, 12, 7, 2],
                [20, 14, 12, 7, 1],
                [20, 14, 12, 6, 4],
                [20, 14, 12, 6, 3],
                [20, 14, 12, 6, 2],
                [20, 14, 12, 6, 1],
                [20, 14, 12, 5, 4],
                [20, 14, 12, 5, 3],
                [20, 14, 12, 5, 2],
                [20, 14, 12, 5, 1],
                [20, 14, 11, 8, 4],
                [20, 14, 11, 8, 3],
                [20, 14, 11, 8, 2],
                [20, 14, 11, 8, 1],
                [20, 14, 11, 7, 4],
                [20, 14, 11, 7, 3],
                [20, 14, 11, 7, 2],
                [20, 14, 11, 7, 1],
                [20, 14, 11, 6, 4],
                [20, 14, 11, 6, 3],
                [20, 14, 11, 6, 2],
                [20, 14, 11, 6, 1],
                [20, 14, 11, 5, 4],
                [20, 14, 11, 5, 3],
                [20, 14, 11, 5, 2],
                [20, 14, 11, 5, 1],
                [20, 14, 10, 8, 4],
                [20, 14, 10, 8, 3],
                [20, 14, 10, 8, 2],
                [20, 14, 10, 8, 1],
                [20, 14, 10, 7, 4],
                [20, 14, 10, 7, 3],
                [20, 14, 10, 7, 2],
                [20, 14, 10, 7, 1],
                [20, 14, 10, 6, 4],
                [20, 14, 10, 6, 3],
                [20, 14, 10, 6, 2],
                [20, 14, 10, 6, 1],
                [20, 14, 10, 5, 4],
                [20, 14, 10, 5, 3],
                [20, 14, 10, 5, 2],
                [20, 14, 10, 5, 1],
                [20, 14, 9, 8, 4],
                [20, 14, 9, 8, 3],
                [20, 14, 9, 8, 2],
                [20, 14, 9, 8, 1],
                [20, 14, 9, 7, 4],
                [20, 14, 9, 7, 3],
                [20, 14, 9, 7, 2],
                [20, 14, 9, 7, 1],
                [20, 14, 9, 6, 4],
                [20, 14, 9, 6, 3],
                [20, 14, 9, 6, 2],
                [20, 14, 9, 6, 1],
                [20, 14, 9, 5, 4],
                [20, 14, 9, 5, 3],
                [20, 14, 9, 5, 2],
                [20, 14, 9, 5, 1],
                [20, 13, 12, 8, 4],
                [20, 13, 12, 8, 3],
                [20, 13, 12, 8, 2],
                [20, 13, 12, 8, 1],
                [20, 13, 12, 7, 4],
                [20, 13, 12, 7, 3],
                [20, 13, 12, 7, 2],
                [20, 13, 12, 7, 1],
                [20, 13, 12, 6, 4],
                [20, 13, 12, 6, 3],
                [20, 13, 12, 6, 2],
                [20, 13, 12, 6, 1],
                [20, 13, 12, 5, 4],
                [20, 13, 12, 5, 3],
                [20, 13, 12, 5, 2],
                [20, 13, 12, 5, 1],
                [20, 13, 11, 8, 4],
                [20, 13, 11, 8, 3],
                [20, 13, 11, 8, 2],
                [20, 13, 11, 8, 1],
                [20, 13, 11, 7, 4],
                [20, 13, 11, 7, 3],
                [20, 13, 11, 7, 2],
                [20, 13, 11, 7, 1],
                [20, 13, 11, 6, 4],
                [20, 13, 11, 6, 3],
                [20, 13, 11, 6, 2],
                [20, 13, 11, 6, 1],
                [20, 13, 11, 5, 4],
                [20, 13, 11, 5, 3],
                [20, 13, 11, 5, 2],
                [20, 13, 11, 5, 1],
                [20, 13, 10, 8, 4],
                [20, 13, 10, 8, 3],
                [20, 13, 10, 8, 2],
                [20, 13, 10, 8, 1],
                [20, 13, 10, 7, 4],
                [20, 13, 10, 7, 3],
                [20, 13, 10, 7, 2],
                [20, 13, 10, 7, 1],
                [20, 13, 10, 6, 4],
                [20, 13, 10, 6, 3],
                [20, 13, 10, 6, 2],
                [20, 13, 10, 6, 1],
                [20, 13, 10, 5, 4],
                [20, 13, 10, 5, 3],
                [20, 13, 10, 5, 2],
                [20, 13, 10, 5, 1],
                [20, 13, 9, 8, 4],
                [20, 13, 9, 8, 3],
                [20, 13, 9, 8, 2],
                [20, 13, 9, 8, 1],
                [20, 13, 9, 7, 4],
                [20, 13, 9, 7, 3],
                [20, 13, 9, 7, 2],
                [20, 13, 9, 7, 1],
                [20, 13, 9, 6, 4],
                [20, 13, 9, 6, 3],
                [20, 13, 9, 6, 2],
                [20, 13, 9, 6, 1],
                [20, 13, 9, 5, 4],
                [20, 13, 9, 5, 3],
                [20, 13, 9, 5, 2],
                [20, 13, 9, 5, 1],
                [19, 16, 12, 8, 4],
                [19, 16, 12, 8, 3],
                [19, 16, 12, 8, 2],
                [19, 16, 12, 8, 1],
                [19, 16, 12, 7, 4],
                [19, 16, 12, 7, 3],
                [19, 16, 12, 7, 2],
                [19, 16, 12, 7, 1],
                [19, 16, 12, 6, 4],
                [19, 16, 12, 6, 3],
                [19, 16, 12, 6, 2],
                [19, 16, 12, 6, 1],
                [19, 16, 12, 5, 4],
                [19, 16, 12, 5, 3],
                [19, 16, 12, 5, 2],
                [19, 16, 12, 5, 1],
                [19, 16, 11, 8, 4],
                [19, 16, 11, 8, 3],
                [19, 16, 11, 8, 2],
                [19, 16, 11, 8, 1],
                [19, 16, 11, 7, 4],
                [19, 16, 11, 7, 3],
                [19, 16, 11, 7, 2],
                [19, 16, 11, 7, 1],
                [19, 16, 11, 6, 4],
                [19, 16, 11, 6, 3],
                [19, 16, 11, 6, 2],
                [19, 16, 11, 6, 1],
                [19, 16, 11, 5, 4],
                [19, 16, 11, 5, 3],
                [19, 16, 11, 5, 2],
                [19, 16, 11, 5, 1],
                [19, 16, 10, 8, 4],
                [19, 16, 10, 8, 3],
                [19, 16, 10, 8, 2],
                [19, 16, 10, 8, 1],
                [19, 16, 10, 7, 4],
                [19, 16, 10, 7, 3],
                [19, 16, 10, 7, 2],
                [19, 16, 10, 7, 1],
                [19, 16, 10, 6, 4],
                [19, 16, 10, 6, 3],
                [19, 16, 10, 6, 2],
                [19, 16, 10, 6, 1],
                [19, 16, 10, 5, 4],
                [19, 16, 10, 5, 3],
                [19, 16, 10, 5, 2],
                [19, 16, 10, 5, 1],
                [19, 16, 9, 8, 4],
                [19, 16, 9, 8, 3],
                [19, 16, 9, 8, 2],
                [19, 16, 9, 8, 1],
                [19, 16, 9, 7, 4],
                [19, 16, 9, 7, 3],
                [19, 16, 9, 7, 2],
                [19, 16, 9, 7, 1],
                [19, 16, 9, 6, 4],
                [19, 16, 9, 6, 3],
                [19, 16, 9, 6, 2],
                [19, 16, 9, 6, 1],
                [19, 16, 9, 5, 4],
                [19, 16, 9, 5, 3],
                [19, 16, 9, 5, 2],
                [19, 16, 9, 5, 1],
                [19, 15, 12, 8, 4],
                [19, 15, 12, 8, 3],
                [19, 15, 12, 8, 2],
                [19, 15, 12, 8, 1],
                [19, 15, 12, 7, 4],
                [19, 15, 12, 7, 3],
                [19, 15, 12, 7, 2],
                [19, 15, 12, 7, 1],
                [19, 15, 12, 6, 4],
                [19, 15, 12, 6, 3],
                [19, 15, 12, 6, 2],
                [19, 15, 12, 6, 1],
                [19, 15, 12, 5, 4],
                [19, 15, 12, 5, 3],
                [19, 15, 12, 5, 2],
                [19, 15, 12, 5, 1],
                [19, 15, 11, 8, 4],
                [19, 15, 11, 8, 3],
                [19, 15, 11, 8, 2],
                [19, 15, 11, 8, 1],
                [19, 15, 11, 7, 4],
                [19, 15, 11, 7, 2],
                [19, 15, 11, 7, 1],
                [19, 15, 11, 6, 4],
                [19, 15, 11, 6, 3],
                [19, 15, 11, 6, 2],
                [19, 15, 11, 6, 1],
                [19, 15, 11, 5, 4],
                [19, 15, 11, 5, 3],
                [19, 15, 11, 5, 2],
                [19, 15, 11, 5, 1],
                [19, 15, 10, 8, 4],
                [19, 15, 10, 8, 3],
                [19, 15, 10, 8, 2],
                [19, 15, 10, 8, 1],
                [19, 15, 10, 7, 4],
                [19, 15, 10, 7, 3],
                [19, 15, 10, 7, 2],
                [19, 15, 10, 7, 1],
                [19, 15, 10, 6, 4],
                [19, 15, 10, 6, 3],
                [19, 15, 10, 6, 2],
                [19, 15, 10, 6, 1],
                [19, 15, 10, 5, 4],
                [19, 15, 10, 5, 3],
                [19, 15, 10, 5, 2],
                [19, 15, 10, 5, 1],
                [19, 15, 9, 8, 4],
                [19, 15, 9, 8, 3],
                [19, 15, 9, 8, 2],
                [19, 15, 9, 8, 1],
                [19, 15, 9, 7, 4],
                [19, 15, 9, 7, 3],
                [19, 15, 9, 7, 2],
                [19, 15, 9, 7, 1],
                [19, 15, 9, 6, 4],
                [19, 15, 9, 6, 3],
                [19, 15, 9, 6, 2],
                [19, 15, 9, 6, 1],
                [19, 15, 9, 5, 4],
                [19, 15, 9, 5, 3],
                [19, 15, 9, 5, 2],
                [19, 15, 9, 5, 1],
                [19, 14, 12, 8, 4],
                [19, 14, 12, 8, 3],
                [19, 14, 12, 8, 2],
                [19, 14, 12, 8, 1],
                [19, 14, 12, 7, 4],
                [19, 14, 12, 7, 3],
                [19, 14, 12, 7, 2],
                [19, 14, 12, 7, 1],
                [19, 14, 12, 6, 4],
                [19, 14, 12, 6, 3],
                [19, 14, 12, 6, 2],
                [19, 14, 12, 6, 1],
                [19, 14, 12, 5, 4],
                [19, 14, 12, 5, 3],
                [19, 14, 12, 5, 2],
                [19, 14, 12, 5, 1],
                [19, 14, 11, 8, 4],
                [19, 14, 11, 8, 3],
                [19, 14, 11, 8, 2],
                [19, 14, 11, 8, 1],
                [19, 14, 11, 7, 4],
                [19, 14, 11, 7, 3],
                [19, 14, 11, 7, 2],
                [19, 14, 11, 7, 1],
                [19, 14, 11, 6, 4],
                [19, 14, 11, 6, 3],
                [19, 14, 11, 6, 2],
                [19, 14, 11, 6, 1],
                [19, 14, 11, 5, 4],
                [19, 14, 11, 5, 3],
                [19, 14, 11, 5, 2],
                [19, 14, 11, 5, 1],
                [19, 14, 10, 8, 4],
                [19, 14, 10, 8, 3],
                [19, 14, 10, 8, 2],
                [19, 14, 10, 8, 1],
                [19, 14, 10, 7, 4],
                [19, 14, 10, 7, 3],
                [19, 14, 10, 7, 2],
                [19, 14, 10, 7, 1],
                [19, 14, 10, 6, 4],
                [19, 14, 10, 6, 3],
                [19, 14, 10, 6, 2],
                [19, 14, 10, 6, 1],
                [19, 14, 10, 5, 4],
                [19, 14, 10, 5, 3],
                [19, 14, 10, 5, 2],
                [19, 14, 10, 5, 1],
                [19, 14, 9, 8, 4],
                [19, 14, 9, 8, 3],
                [19, 14, 9, 8, 2],
                [19, 14, 9, 8, 1],
                [19, 14, 9, 7, 4],
                [19, 14, 9, 7, 3],
                [19, 14, 9, 7, 2],
                [19, 14, 9, 7, 1],
                [19, 14, 9, 6, 4],
                [19, 14, 9, 6, 3],
                [19, 14, 9, 6, 2],
                [19, 14, 9, 6, 1],
                [19, 14, 9, 5, 4],
                [19, 14, 9, 5, 3],
                [19, 14, 9, 5, 2],
                [19, 14, 9, 5, 1],
                [19, 13, 12, 8, 4],
                [19, 13, 12, 8, 3],
                [19, 13, 12, 8, 2],
                [19, 13, 12, 8, 1],
                [19, 13, 12, 7, 4],
                [19, 13, 12, 7, 3],
                [19, 13, 12, 7, 2],
                [19, 13, 12, 7, 1],
                [19, 13, 12, 6, 4],
                [19, 13, 12, 6, 3],
                [19, 13, 12, 6, 2],
                [19, 13, 12, 6, 1],
                [19, 13, 12, 5, 4],
                [19, 13, 12, 5, 3],
                [19, 13, 12, 5, 2],
                [19, 13, 12, 5, 1],
                [19, 13, 11, 8, 4],
                [19, 13, 11, 8, 3],
                [19, 13, 11, 8, 2],
                [19, 13, 11, 8, 1],
                [19, 13, 11, 7, 4],
                [19, 13, 11, 7, 3],
                [19, 13, 11, 7, 2],
                [19, 13, 11, 7, 1],
                [19, 13, 11, 6, 4],
                [19, 13, 11, 6, 3],
                [19, 13, 11, 6, 2],
                [19, 13, 11, 6, 1],
                [19, 13, 11, 5, 4],
                [19, 13, 11, 5, 3],
                [19, 13, 11, 5, 2],
                [19, 13, 11, 5, 1],
                [19, 13, 10, 8, 4],
                [19, 13, 10, 8, 3],
                [19, 13, 10, 8, 2],
                [19, 13, 10, 8, 1],
                [19, 13, 10, 7, 4],
                [19, 13, 10, 7, 3],
                [19, 13, 10, 7, 2],
                [19, 13, 10, 7, 1],
                [19, 13, 10, 6, 4],
                [19, 13, 10, 6, 3],
                [19, 13, 10, 6, 2],
                [19, 13, 10, 6, 1],
                [19, 13, 10, 5, 4],
                [19, 13, 10, 5, 3],
                [19, 13, 10, 5, 2],
                [19, 13, 10, 5, 1],
                [19, 13, 9, 8, 4],
                [19, 13, 9, 8, 3],
                [19, 13, 9, 8, 2],
                [19, 13, 9, 8, 1],
                [19, 13, 9, 7, 4],
                [19, 13, 9, 7, 3],
                [19, 13, 9, 7, 2],
                [19, 13, 9, 7, 1],
                [19, 13, 9, 6, 4],
                [19, 13, 9, 6, 3],
                [19, 13, 9, 6, 2],
                [19, 13, 9, 6, 1],
                [19, 13, 9, 5, 4],
                [19, 13, 9, 5, 3],
                [19, 13, 9, 5, 2],
                [19, 13, 9, 5, 1],
                [18, 16, 12, 8, 4],
                [18, 16, 12, 8, 3],
                [18, 16, 12, 8, 2],
                [18, 16, 12, 8, 1],
                [18, 16, 12, 7, 4],
                [18, 16, 12, 7, 3],
                [18, 16, 12, 7, 2],
                [18, 16, 12, 7, 1],
                [18, 16, 12, 6, 4],
                [18, 16, 12, 6, 3],
                [18, 16, 12, 6, 2],
                [18, 16, 12, 6, 1],
                [18, 16, 12, 5, 4],
                [18, 16, 12, 5, 3],
                [18, 16, 12, 5, 2],
                [18, 16, 12, 5, 1],
                [18, 16, 11, 8, 4],
                [18, 16, 11, 8, 3],
                [18, 16, 11, 8, 2],
                [18, 16, 11, 8, 1],
                [18, 16, 11, 7, 4],
                [18, 16, 11, 7, 3],
                [18, 16, 11, 7, 2],
                [18, 16, 11, 7, 1],
                [18, 16, 11, 6, 4],
                [18, 16, 11, 6, 3],
                [18, 16, 11, 6, 2],
                [18, 16, 11, 6, 1],
                [18, 16, 11, 5, 4],
                [18, 16, 11, 5, 3],
                [18, 16, 11, 5, 2],
                [18, 16, 11, 5, 1],
                [18, 16, 10, 8, 4],
                [18, 16, 10, 8, 3],
                [18, 16, 10, 8, 2],
                [18, 16, 10, 8, 1],
                [18, 16, 10, 7, 4],
                [18, 16, 10, 7, 3],
                [18, 16, 10, 7, 2],
                [18, 16, 10, 7, 1],
                [18, 16, 10, 6, 4],
                [18, 16, 10, 6, 3],
                [18, 16, 10, 6, 2],
                [18, 16, 10, 6, 1],
                [18, 16, 10, 5, 4],
                [18, 16, 10, 5, 3],
                [18, 16, 10, 5, 2],
                [18, 16, 10, 5, 1],
                [18, 16, 9, 8, 4],
                [18, 16, 9, 8, 3],
                [18, 16, 9, 8, 2],
                [18, 16, 9, 8, 1],
                [18, 16, 9, 7, 4],
                [18, 16, 9, 7, 3],
                [18, 16, 9, 7, 2],
                [18, 16, 9, 7, 1],
                [18, 16, 9, 6, 4],
                [18, 16, 9, 6, 3],
                [18, 16, 9, 6, 2],
                [18, 16, 9, 6, 1],
                [18, 16, 9, 5, 4],
                [18, 16, 9, 5, 3],
                [18, 16, 9, 5, 2],
                [18, 16, 9, 5, 1],
                [18, 15, 12, 8, 4],
                [18, 15, 12, 8, 3],
                [18, 15, 12, 8, 2],
                [18, 15, 12, 8, 1],
                [18, 15, 12, 7, 4],
                [18, 15, 12, 7, 3],
                [18, 15, 12, 7, 2],
                [18, 15, 12, 7, 1],
                [18, 15, 12, 6, 4],
                [18, 15, 12, 6, 3],
                [18, 15, 12, 6, 2],
                [18, 15, 12, 6, 1],
                [18, 15, 12, 5, 4],
                [18, 15, 12, 5, 3],
                [18, 15, 12, 5, 2],
                [18, 15, 12, 5, 1],
                [18, 15, 11, 8, 4],
                [18, 15, 11, 8, 3],
                [18, 15, 11, 8, 2],
                [18, 15, 11, 8, 1],
                [18, 15, 11, 7, 4],
                [18, 15, 11, 7, 3],
                [18, 15, 11, 7, 2],
                [18, 15, 11, 7, 1],
                [18, 15, 11, 6, 4],
                [18, 15, 11, 6, 3],
                [18, 15, 11, 6, 2],
                [18, 15, 11, 6, 1],
                [18, 15, 11, 5, 4],
                [18, 15, 11, 5, 3],
                [18, 15, 11, 5, 2],
                [18, 15, 11, 5, 1],
                [18, 15, 10, 8, 4],
                [18, 15, 10, 8, 3],
                [18, 15, 10, 8, 2],
                [18, 15, 10, 8, 1],
                [18, 15, 10, 7, 4],
                [18, 15, 10, 7, 3],
                [18, 15, 10, 7, 2],
                [18, 15, 10, 7, 1],
                [18, 15, 10, 6, 4],
                [18, 15, 10, 6, 3],
                [18, 15, 10, 6, 2],
                [18, 15, 10, 6, 1],
                [18, 15, 10, 5, 4],
                [18, 15, 10, 5, 3],
                [18, 15, 10, 5, 2],
                [18, 15, 10, 5, 1],
                [18, 15, 9, 8, 4],
                [18, 15, 9, 8, 3],
                [18, 15, 9, 8, 2],
                [18, 15, 9, 8, 1],
                [18, 15, 9, 7, 4],
                [18, 15, 9, 7, 3],
                [18, 15, 9, 7, 2],
                [18, 15, 9, 7, 1],
                [18, 15, 9, 6, 4],
                [18, 15, 9, 6, 3],
                [18, 15, 9, 6, 2],
                [18, 15, 9, 6, 1],
                [18, 15, 9, 5, 4],
                [18, 15, 9, 5, 3],
                [18, 15, 9, 5, 2],
                [18, 15, 9, 5, 1],
                [18, 14, 12, 8, 4],
                [18, 14, 12, 8, 3],
                [18, 14, 12, 8, 2],
                [18, 14, 12, 8, 1],
                [18, 14, 12, 7, 4],
                [18, 14, 12, 7, 3],
                [18, 14, 12, 7, 2],
                [18, 14, 12, 7, 1],
                [18, 14, 12, 6, 4],
                [18, 14, 12, 6, 3],
                [18, 14, 12, 6, 2],
                [18, 14, 12, 6, 1],
                [18, 14, 12, 5, 4],
                [18, 14, 12, 5, 3],
                [18, 14, 12, 5, 2],
                [18, 14, 12, 5, 1],
                [18, 14, 11, 8, 4],
                [18, 14, 11, 8, 3],
                [18, 14, 11, 8, 2],
                [18, 14, 11, 8, 1],
                [18, 14, 11, 7, 4],
                [18, 14, 11, 7, 3],
                [18, 14, 11, 7, 2],
                [18, 14, 11, 7, 1],
                [18, 14, 11, 6, 4],
                [18, 14, 11, 6, 3],
                [18, 14, 11, 6, 2],
                [18, 14, 11, 6, 1],
                [18, 14, 11, 5, 4],
                [18, 14, 11, 5, 3],
                [18, 14, 11, 5, 2],
                [18, 14, 11, 5, 1],
                [18, 14, 10, 8, 4],
                [18, 14, 10, 8, 3],
                [18, 14, 10, 8, 2],
                [18, 14, 10, 8, 1],
                [18, 14, 10, 7, 4],
                [18, 14, 10, 7, 3],
                [18, 14, 10, 7, 2],
                [18, 14, 10, 7, 1],
                [18, 14, 10, 6, 4],
                [18, 14, 10, 6, 3],
                [18, 14, 10, 6, 1],
                [18, 14, 10, 5, 4],
                [18, 14, 10, 5, 3],
                [18, 14, 10, 5, 2],
                [18, 14, 10, 5, 1],
                [18, 14, 9, 8, 4],
                [18, 14, 9, 8, 3],
                [18, 14, 9, 8, 2],
                [18, 14, 9, 8, 1],
                [18, 14, 9, 7, 4],
                [18, 14, 9, 7, 3],
                [18, 14, 9, 7, 2],
                [18, 14, 9, 7, 1],
                [18, 14, 9, 6, 4],
                [18, 14, 9, 6, 3],
                [18, 14, 9, 6, 2],
                [18, 14, 9, 6, 1],
                [18, 14, 9, 5, 4],
                [18, 14, 9, 5, 3],
                [18, 14, 9, 5, 2],
                [18, 14, 9, 5, 1],
                [18, 13, 12, 8, 4],
                [18, 13, 12, 8, 3],
                [18, 13, 12, 8, 2],
                [18, 13, 12, 8, 1],
                [18, 13, 12, 7, 4],
                [18, 13, 12, 7, 3],
                [18, 13, 12, 7, 2],
                [18, 13, 12, 7, 1],
                [18, 13, 12, 6, 4],
                [18, 13, 12, 6, 3],
                [18, 13, 12, 6, 2],
                [18, 13, 12, 6, 1],
                [18, 13, 12, 5, 4],
                [18, 13, 12, 5, 3],
                [18, 13, 12, 5, 2],
                [18, 13, 12, 5, 1],
                [18, 13, 11, 8, 4],
                [18, 13, 11, 8, 3],
                [18, 13, 11, 8, 2],
                [18, 13, 11, 8, 1],
                [18, 13, 11, 7, 4],
                [18, 13, 11, 7, 3],
                [18, 13, 11, 7, 2],
                [18, 13, 11, 7, 1],
                [18, 13, 11, 6, 4],
                [18, 13, 11, 6, 3],
                [18, 13, 11, 6, 2],
                [18, 13, 11, 6, 1],
                [18, 13, 11, 5, 4],
                [18, 13, 11, 5, 3],
                [18, 13, 11, 5, 2],
                [18, 13, 11, 5, 1],
                [18, 13, 10, 8, 4],
                [18, 13, 10, 8, 3],
                [18, 13, 10, 8, 2],
                [18, 13, 10, 8, 1],
                [18, 13, 10, 7, 4],
                [18, 13, 10, 7, 3],
                [18, 13, 10, 7, 2],
                [18, 13, 10, 7, 1],
                [18, 13, 10, 6, 4],
                [18, 13, 10, 6, 3],
                [18, 13, 10, 6, 2],
                [18, 13, 10, 6, 1],
                [18, 13, 10, 5, 4],
                [18, 13, 10, 5, 3],
                [18, 13, 10, 5, 2],
                [18, 13, 10, 5, 1],
                [18, 13, 9, 8, 4],
                [18, 13, 9, 8, 3],
                [18, 13, 9, 8, 2],
                [18, 13, 9, 8, 1],
                [18, 13, 9, 7, 4],
                [18, 13, 9, 7, 3],
                [18, 13, 9, 7, 2],
                [18, 13, 9, 7, 1],
                [18, 13, 9, 6, 4],
                [18, 13, 9, 6, 3],
                [18, 13, 9, 6, 2],
                [18, 13, 9, 6, 1],
                [18, 13, 9, 5, 4],
                [18, 13, 9, 5, 3],
                [18, 13, 9, 5, 2],
                [18, 13, 9, 5, 1],
                [17, 16, 12, 8, 4],
                [17, 16, 12, 8, 3],
                [17, 16, 12, 8, 2],
                [17, 16, 12, 8, 1],
                [17, 16, 12, 7, 4],
                [17, 16, 12, 7, 3],
                [17, 16, 12, 7, 2],
                [17, 16, 12, 7, 1],
                [17, 16, 12, 6, 4],
                [17, 16, 12, 6, 3],
                [17, 16, 12, 6, 2],
                [17, 16, 12, 6, 1],
                [17, 16, 12, 5, 4],
                [17, 16, 12, 5, 3],
                [17, 16, 12, 5, 2],
                [17, 16, 12, 5, 1],
                [17, 16, 11, 8, 4],
                [17, 16, 11, 8, 3],
                [17, 16, 11, 8, 2],
                [17, 16, 11, 8, 1],
                [17, 16, 11, 7, 4],
                [17, 16, 11, 7, 3],
                [17, 16, 11, 7, 2],
                [17, 16, 11, 7, 1],
                [17, 16, 11, 6, 4],
                [17, 16, 11, 6, 3],
                [17, 16, 11, 6, 2],
                [17, 16, 11, 6, 1],
                [17, 16, 11, 5, 4],
                [17, 16, 11, 5, 3],
                [17, 16, 11, 5, 2],
                [17, 16, 11, 5, 1],
                [17, 16, 10, 8, 4],
                [17, 16, 10, 8, 3],
                [17, 16, 10, 8, 2],
                [17, 16, 10, 8, 1],
                [17, 16, 10, 7, 4],
                [17, 16, 10, 7, 3],
                [17, 16, 10, 7, 2],
                [17, 16, 10, 7, 1],
                [17, 16, 10, 6, 4],
                [17, 16, 10, 6, 3],
                [17, 16, 10, 6, 2],
                [17, 16, 10, 6, 1],
                [17, 16, 10, 5, 4],
                [17, 16, 10, 5, 3],
                [17, 16, 10, 5, 2],
                [17, 16, 10, 5, 1],
                [17, 16, 9, 8, 4],
                [17, 16, 9, 8, 3],
                [17, 16, 9, 8, 2],
                [17, 16, 9, 8, 1],
                [17, 16, 9, 7, 4],
                [17, 16, 9, 7, 3],
                [17, 16, 9, 7, 2],
                [17, 16, 9, 7, 1],
                [17, 16, 9, 6, 4],
                [17, 16, 9, 6, 3],
                [17, 16, 9, 6, 2],
                [17, 16, 9, 6, 1],
                [17, 16, 9, 5, 4],
                [17, 16, 9, 5, 3],
                [17, 16, 9, 5, 2],
                [17, 16, 9, 5, 1],
                [17, 15, 12, 8, 4],
                [17, 15, 12, 8, 3],
                [17, 15, 12, 8, 2],
                [17, 15, 12, 8, 1],
                [17, 15, 12, 7, 4],
                [17, 15, 12, 7, 3],
                [17, 15, 12, 7, 2],
                [17, 15, 12, 7, 1],
                [17, 15, 12, 6, 4],
                [17, 15, 12, 6, 3],
                [17, 15, 12, 6, 2],
                [17, 15, 12, 6, 1],
                [17, 15, 12, 5, 4],
                [17, 15, 12, 5, 3],
                [17, 15, 12, 5, 2],
                [17, 15, 12, 5, 1],
                [17, 15, 11, 8, 4],
                [17, 15, 11, 8, 3],
                [17, 15, 11, 8, 2],
                [17, 15, 11, 8, 1],
                [17, 15, 11, 7, 4],
                [17, 15, 11, 7, 3],
                [17, 15, 11, 7, 2],
                [17, 15, 11, 7, 1],
                [17, 15, 11, 6, 4],
                [17, 15, 11, 6, 3],
                [17, 15, 11, 6, 2],
                [17, 15, 11, 6, 1],
                [17, 15, 11, 5, 4],
                [17, 15, 11, 5, 3],
                [17, 15, 11, 5, 2],
                [17, 15, 11, 5, 1],
                [17, 15, 10, 8, 4],
                [17, 15, 10, 8, 3],
                [17, 15, 10, 8, 2],
                [17, 15, 10, 8, 1],
                [17, 15, 10, 7, 4],
                [17, 15, 10, 7, 3],
                [17, 15, 10, 7, 2],
                [17, 15, 10, 7, 1],
                [17, 15, 10, 6, 4],
                [17, 15, 10, 6, 3],
                [17, 15, 10, 6, 2],
                [17, 15, 10, 6, 1],
                [17, 15, 10, 5, 4],
                [17, 15, 10, 5, 3],
                [17, 15, 10, 5, 2],
                [17, 15, 10, 5, 1],
                [17, 15, 9, 8, 4],
                [17, 15, 9, 8, 3],
                [17, 15, 9, 8, 2],
                [17, 15, 9, 8, 1],
                [17, 15, 9, 7, 4],
                [17, 15, 9, 7, 3],
                [17, 15, 9, 7, 2],
                [17, 15, 9, 7, 1],
                [17, 15, 9, 6, 4],
                [17, 15, 9, 6, 3],
                [17, 15, 9, 6, 2],
                [17, 15, 9, 6, 1],
                [17, 15, 9, 5, 4],
                [17, 15, 9, 5, 3],
                [17, 15, 9, 5, 2],
                [17, 15, 9, 5, 1],
                [17, 14, 12, 8, 4],
                [17, 14, 12, 8, 3],
                [17, 14, 12, 8, 2],
                [17, 14, 12, 8, 1],
                [17, 14, 12, 7, 4],
                [17, 14, 12, 7, 3],
                [17, 14, 12, 7, 2],
                [17, 14, 12, 7, 1],
                [17, 14, 12, 6, 4],
                [17, 14, 12, 6, 3],
                [17, 14, 12, 6, 2],
                [17, 14, 12, 6, 1],
                [17, 14, 12, 5, 4],
                [17, 14, 12, 5, 3],
                [17, 14, 12, 5, 2],
                [17, 14, 12, 5, 1],
                [17, 14, 11, 8, 4],
                [17, 14, 11, 8, 3],
                [17, 14, 11, 8, 2],
                [17, 14, 11, 8, 1],
                [17, 14, 11, 7, 4],
                [17, 14, 11, 7, 3],
                [17, 14, 11, 7, 2],
                [17, 14, 11, 7, 1],
                [17, 14, 11, 6, 4],
                [17, 14, 11, 6, 3],
                [17, 14, 11, 6, 2],
                [17, 14, 11, 6, 1],
                [17, 14, 11, 5, 4],
                [17, 14, 11, 5, 3],
                [17, 14, 11, 5, 2],
                [17, 14, 11, 5, 1],
                [17, 14, 10, 8, 4],
                [17, 14, 10, 8, 3],
                [17, 14, 10, 8, 2],
                [17, 14, 10, 8, 1],
                [17, 14, 10, 7, 4],
                [17, 14, 10, 7, 3],
                [17, 14, 10, 7, 2],
                [17, 14, 10, 7, 1],
                [17, 14, 10, 6, 4],
                [17, 14, 10, 6, 3],
                [17, 14, 10, 6, 2],
                [17, 14, 10, 6, 1],
                [17, 14, 10, 5, 4],
                [17, 14, 10, 5, 3],
                [17, 14, 10, 5, 2],
                [17, 14, 10, 5, 1],
                [17, 14, 9, 8, 4],
                [17, 14, 9, 8, 3],
                [17, 14, 9, 8, 2],
                [17, 14, 9, 8, 1],
                [17, 14, 9, 7, 4],
                [17, 14, 9, 7, 3],
                [17, 14, 9, 7, 2],
                [17, 14, 9, 7, 1],
                [17, 14, 9, 6, 4],
                [17, 14, 9, 6, 3],
                [17, 14, 9, 6, 2],
                [17, 14, 9, 6, 1],
                [17, 14, 9, 5, 4],
                [17, 14, 9, 5, 3],
                [17, 14, 9, 5, 2],
                [17, 14, 9, 5, 1],
                [17, 13, 12, 8, 4],
                [17, 13, 12, 8, 3],
                [17, 13, 12, 8, 2],
                [17, 13, 12, 8, 1],
                [17, 13, 12, 7, 4],
                [17, 13, 12, 7, 3],
                [17, 13, 12, 7, 2],
                [17, 13, 12, 7, 1],
                [17, 13, 12, 6, 4],
                [17, 13, 12, 6, 3],
                [17, 13, 12, 6, 2],
                [17, 13, 12, 6, 1],
                [17, 13, 12, 5, 4],
                [17, 13, 12, 5, 3],
                [17, 13, 12, 5, 2],
                [17, 13, 12, 5, 1],
                [17, 13, 11, 8, 4],
                [17, 13, 11, 8, 3],
                [17, 13, 11, 8, 2],
                [17, 13, 11, 8, 1],
                [17, 13, 11, 7, 4],
                [17, 13, 11, 7, 3],
                [17, 13, 11, 7, 2],
                [17, 13, 11, 7, 1],
                [17, 13, 11, 6, 4],
                [17, 13, 11, 6, 3],
                [17, 13, 11, 6, 2],
                [17, 13, 11, 6, 1],
                [17, 13, 11, 5, 4],
                [17, 13, 11, 5, 3],
                [17, 13, 11, 5, 2],
                [17, 13, 11, 5, 1],
                [17, 13, 10, 8, 4],
                [17, 13, 10, 8, 3],
                [17, 13, 10, 8, 2],
                [17, 13, 10, 8, 1],
                [17, 13, 10, 7, 4],
                [17, 13, 10, 7, 3],
                [17, 13, 10, 7, 2],
                [17, 13, 10, 7, 1],
                [17, 13, 10, 6, 4],
                [17, 13, 10, 6, 3],
                [17, 13, 10, 6, 2],
                [17, 13, 10, 6, 1],
                [17, 13, 10, 5, 4],
                [17, 13, 10, 5, 3],
                [17, 13, 10, 5, 2],
                [17, 13, 10, 5, 1],
                [17, 13, 9, 8, 4],
                [17, 13, 9, 8, 3],
                [17, 13, 9, 8, 2],
                [17, 13, 9, 8, 1],
                [17, 13, 9, 7, 4],
                [17, 13, 9, 7, 3],
                [17, 13, 9, 7, 2],
                [17, 13, 9, 7, 1],
                [17, 13, 9, 6, 4],
                [17, 13, 9, 6, 3],
                [17, 13, 9, 6, 2],
                [17, 13, 9, 6, 1],
                [17, 13, 9, 5, 4],
                [17, 13, 9, 5, 3],
                [17, 13, 9, 5, 2]
            ];

            foreach ($cardStraightArray as $key => $item) {
                if ($cardArray == $item) {
                    $isFiveCardStraight = "true";
                    $index              = $key;
                }
            }
        } else {
            echo "CARD_LENGTH_NOT_EQUAL_FIVE";
        }

        $returnInfo          = [];
        $returnInfo["flag"]  = $isFiveCardStraight;
        $returnInfo["index"] = $index;
        return $returnInfo;
    }

    /**
     * 是否是三条
     */
    function isThreeSameWithTwoDiff($cardArray) {
        $flag      = "false";
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 3) { //[3,1,1]说明是三个不同组
            if ((current($samePositionArray) == 3)) {  //三条在开头
                // echo "<br/>";
                // echo "三条在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            } elseif (end($samePositionArray) == 3) { //[1,1,3]3条在结尾
                // echo "<br/>";
                // echo "三条在结尾,花色位置:" . $cardPositionArray[end($cardArray)];
                // echo "<br/>";
                $flag = "true";
            } else { //3条在中间 [1,3,1]
                // echo "<br/>";
                reset($samePositionArray); //指针重新指向第一个位置
                next($samePositionArray); //移动一个位置,即第二个位置
                // echo "三条在中间,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                if (current($samePositionArray) == 3) {
                    $flag = "true";
                }

            }
        }

        return $flag;
    }

    function isThreeSameWithTwoDiffDetail($cardArray) {
        $flag         = "false";
        $index        = -1;
        $cardPosition = "";
        $fullArray    = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 3) { //说明是三个不同组
            if ((current($samePositionArray) == 3)) {  //三条在开头
                /**
                 * 3 1 1
                 */
                // echo "<br/>";
                // echo "三条在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "head";
                // echo "<br/>";
                $flag = "true";
            } elseif (end($samePositionArray) == 3) { //3条在结尾
                /**
                 * 1 1 3
                 */
                // echo "<br/>";
                // echo "三条在结尾,花色位置:" . $cardPositionArray[end($cardArray)];
                $index        = $cardPositionArray[end($cardArray)];
                $cardPosition = "end";
                // echo "<br/>";
                $flag = "true";

            } else {
                /**
                 * 1 3 1
                 * -------------------------------------------------------
                 * 这里修正bug:
                 * 由于对子212和三条在牌型上非常相像,所以要严格区别三的位置
                 */
                reset($samePositionArray);
                next($samePositionArray);
                if (current($samePositionArray) == 3) {  //3条在中间
                    // echo "<br/>";
                    reset($cardArray); //指针重新指向第一个位置
                    next($cardArray); //移动一个位置,即第二个位置
                    // echo "三条在中间,花色位置:" . $cardPositionArray[current($cardArray)];
                    $index        = $cardPositionArray[current($cardArray)];
                    $cardPosition = "middle";
                    // echo "<br/>";
                    $flag = "true";
                }
            }
        }

        $returnInfo             = [];
        $returnInfo["flag"]     = $flag;
        $returnInfo["index"]    = $index;
        $returnInfo["position"] = $cardPosition;
        return $returnInfo;
    }

    /**
     * 是否是两对
     */
    function isTwoPairWithOneDiff($cardArray) {
        rsort($cardArray);
        // echo "对子array:" . json_encode($cardArray) . "<br/>";
        $flag = "false";
        //牌型如[1,2,2] [2,1,2],[2,2,1]
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);
        // echo "count:" . count($samePositionArray);
        if (count($samePositionArray) == 3) {
            // echo "count==3";
            if (current($samePositionArray) == 2 && end($samePositionArray) == 2) { //[2,1,2] 判断元素在开头
                // echo "<br/>";
                // echo "判断在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }

            reset($samePositionArray);
            if (current($samePositionArray) == 2 && end($samePositionArray) == 1) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "判断在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }

            reset($samePositionArray);
            if (current($samePositionArray) == 1 && end($samePositionArray) == 2) { //[1,2,2] 判断元素在中间
                // echo "<br/>";
                reset($cardArray); //指针重新指向第一个位置
                next($cardArray); //移动一个位置,即第二个位置
                // echo "判断在中间,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }
        }

        return $flag;
    }

    function isTwoPairWithOneDiffDetail($cardArray) {
        rsort($cardArray);
        $flag         = "false";
        $index        = -1;
        $cardPosition = "";
        //牌型如[1,2,2] [2,1,2],[2,2,1]
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 3) {
            if (current($samePositionArray) == 2 && end($samePositionArray) == 2) { //[2,1,2] 判断元素在开头
                // echo "<br/>";
                // echo "判断在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "212";
                // echo "<br/>";
                $flag = "true";
            }

            reset($samePositionArray);
            if (current($samePositionArray) == 2 && end($samePositionArray) == 1) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "判断在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "221";
                // echo "<br/>";
                $flag = "true";
            }

            reset($samePositionArray);
            if (current($samePositionArray) == 1 && end($samePositionArray) == 2) { //[1,2,2] 判断元素在中间
                // echo "<br/>";
                reset($cardArray); //指针重新指向第一个位置
                next($cardArray); //移动一个位置,即第二个位置
                // echo "判断在中间,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "122";
                // echo "<br/>";
                $flag = "true";
            }
        }

        $returnInfo             = [];
        $returnInfo["card"]     = $cardArray;
        $returnInfo["flag"]     = $flag;
        $returnInfo["index"]    = $index;
        $returnInfo["position"] = $cardPosition;
        return $returnInfo;
    }

    /**
     * 是否是对子
     * 对子的情况
     * 2,1,1,1
     * 1,2,1,1
     * 1,1,2,1
     * 1,1,1,2
     */
    function isOnePairWithThreeDiff($cardArray) {
        $flag      = "false";
        $fullArray = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        if (count($samePositionArray) == 4) { //出现这种情况,说明有对子存在
            //如果出现相应的组里面为2,那么说明那个组为对子
            //对子在开头
            if (current($samePositionArray) == 2) { //
                // echo "<br/>";
                // echo "对子在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }

            //对子在第二个位置
            reset($samePositionArray);
            next($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            next($cardArray); //移动一个位置,即第二个位置
            if (current($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第二个位置,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }

            //对子在第三个位置
            reset($samePositionArray);
            next($samePositionArray);
            next($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            next($cardArray); //移动一个位置,即第二个位置
            next($cardArray); //移动一个位置,即第二个位置
            if (current($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第三个位置,花色位置:" . $cardPositionArray[current($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }

            //对子在最后
            reset($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            if (end($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第四个位置,花色位置:" . $cardPositionArray[end($cardArray)];
                // echo "<br/>";
                $flag = "true";
            }
        }
        return $flag;
    }

    function isOnePairWithThreeDiffDetail($cardArray) {
        rsort($cardArray);
        $flag         = "false";
        $index        = -1;
        $cardPosition = "";
        $fullArray    = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        print_r($samePositionArray);

        if (count($samePositionArray) == 4) { //出现这种情况,说明有对子存在
            //如果出现相应的组里面为2,那么说明那个组为对子
            //对子在开头
            if (current($samePositionArray) == 2) { //
                // echo "<br/>";
                // echo "对子在开头,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "2111";
                // echo "<br/>";
                $flag = "true";
            }

            //对子在第二个位置
            reset($samePositionArray);
            next($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            next($cardArray); //移动一个位置,即第二个位置
            if (current($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第二个位置,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "1211";
                // echo "<br/>";
                $flag = "true";
            }

            //对子在第三个位置
            reset($samePositionArray);
            next($samePositionArray);
            next($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            next($cardArray); //移动一个位置,即第二个位置
            next($cardArray); //移动一个位置,即第二个位置
            if (current($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第三个位置,花色位置:" . $cardPositionArray[current($cardArray)];
                $index        = $cardPositionArray[current($cardArray)];
                $cardPosition = "1121";
                // echo "<br/>";
                $flag = "true";
            }

            //对子在最后
            reset($samePositionArray);
            reset($cardArray); //指针重新指向第一个位置
            if (end($samePositionArray) == 2) { //[2,2,1] 判断元素在开头
                // echo "<br/>";
                // echo "对子在第四个位置,花色位置:" . $cardPositionArray[end($cardArray)];
                $index        = $cardPositionArray[end($cardArray)];
                $cardPosition = "1112";
                // echo "<br/>";
                $flag = "true";
            }
        }

        $returnInfo             = [];
        $returnInfo["flag"]     = $flag;
        $returnInfo["index"]    = $index;
        $returnInfo["position"] = $cardPosition;
        return $returnInfo;
    }

    /**
     * 根据传输的卡牌数值得到卡牌的详细信息,包括花色,牌面数值
     */
    function getCardDetail($cardValue) {
        // echo 'test';
        $returnCard = [];
        switch ($cardValue) {
            case "1": //方块2
                $returnCard = [1, 2, 1];
                break;
            case "2": //梅花2
                $returnCard = [2, 2, 2];
                break;
            case "3": //红心2
                $returnCard = [3, 2, 3];
                break;
            case "4": //黑桃2
                $returnCard = [4, 2, 4];
                break;
            case "5": //方块3
                $returnCard = [1, 3, 5];
                break;
            case "6": //梅花3
                $returnCard = [2, 3, 6];
                break;
            case "7": //红心3
                $returnCard = [3, 3, 7];
                break;
            case "8": //黑桃3
                $returnCard = [4, 3, 8];
                break;
            case "9": //方块4
                $returnCard = [1, 4, 9];
                break;
            case "10": //梅花4
                $returnCard = [2, 4, 10];
                break;
            case "11": //红心4
                $returnCard = [3, 4, 11];
                break;
            case "12": //黑桃4
                $returnCard = [4, 4, 12];
                break;
            case "13": //方块5
                $returnCard = [1, 5, 13];
                break;
            case "14": //梅花5
                $returnCard = [2, 5, 14];
                break;
            case "15": //红心5
                $returnCard = [3, 5, 15];
                break;
            case "16": //黑桃5
                $returnCard = [4, 5, 16];
                break;
            case "17": //方块6
                $returnCard = [1, 6, 17];
                break;
            case "18": //梅花6
                $returnCard = [2, 6, 18];
                break;
            case "19": //红心6
                $returnCard = [3, 6, 19];
                break;
            case "20": //黑桃6
                $returnCard = [4, 6, 20];
                break;
            case "21": //方块7
                $returnCard = [1, 7, 21];
                break;
            case "22": //梅花7
                $returnCard = [2, 7, 22];
                break;
            case "23": //红心7
                $returnCard = [3, 7, 23];
                break;
            case "24": //黑桃7
                $returnCard = [4, 7, 24];
                break;
            case "25": //方块8
                $returnCard = [1, 8, 25];
                break;
            case "26": //梅花8
                $returnCard = [2, 8, 26];
                break;
            case "27": //红心8
                $returnCard = [3, 8, 27];
                break;
            case "28": //黑桃8
                $returnCard = [4, 8, 28];
                break;
            case "29": //方块9
                $returnCard = [1, 9, 29];
                break;
            case "30": //梅花9
                $returnCard = [2, 9, 30];
                break;
            case "31": //红心9
                $returnCard = [3, 9, 31];
                break;
            case "32": //黑桃9
                $returnCard = [4, 9, 32];
                break;
            case "33": //方块10
                $returnCard = [1, 10, 33];
                break;
            case "34": //梅花10
                $returnCard = [2, 10, 34];
                break;
            case "35": //红心10
                $returnCard = [3, 10, 35];
                break;
            case "36": //黑桃10
                $returnCard = [4, 10, 36];
                break;
            case "37": //方块J
                $returnCard = [1, 11, 37];
                break;
            case "38": //梅花J
                $returnCard = [2, 11, 38];
                break;
            case "39": //红心J
                $returnCard = [3, 11, 39];
                break;
            case "40": //黑桃J
                $returnCard = [4, 11, 40];
                break;
            case "41": //方块Q
                $returnCard = [1, 12, 41];
                break;
            case "42": //梅花Q
                $returnCard = [2, 12, 42];
                break;
            case "43": //红心Q
                $returnCard = [3, 12, 43];
                break;
            case "44": //黑桃Q
                $returnCard = [4, 12, 44];
                break;
            case "45": //方块K
                $returnCard = [1, 13, 45];
                break;
            case "46": //梅花K
                $returnCard = [2, 13, 46];
                break;
            case "47": //红心K
                $returnCard = [3, 13, 47];
                break;
            case "48": //黑桃K
                $returnCard = [4, 13, 48];
                break;
            case "49": //方块A
                $returnCard = [1, 14, 49];
                break;
            case "50": //梅花A
                $returnCard = [2, 14, 50];
                break;
            case "51": //红心A
                $returnCard = [3, 14, 51];
                break;
            case "52": //黑桃A
                $returnCard = [4, 14, 52];
                break;
            default: //错误
                $returnCard = [0, 0, 0];
                break;
        }

        return $returnCard;
    }

    /**
     * 根据一组牌转换成详细的卡牌信息
     */
    function convertCardDetail($cardArray, $isForceThirteen = false) {
        $cardDetailArray = [];
        if ($isForceThirteen == true) {
            if (count($cardArray) < 13) {
                return "ARRAY_LENGTH_ERROR";
            } else {
                foreach ($cardArray as $item) {
                    $cardDetail = getCardDetail($item);
                    array_push($cardDetailArray, $cardDetail);
                }
            }
        } else {
            foreach ($cardArray as $item) {
                $cardDetail = getCardDetail($item);
                array_push($cardDetailArray, $cardDetail);
            }
        }

        // var_dump($cardDetailArray);
        echo "cardDetailArray:" . json_encode($cardDetailArray) . "\n";
        return $cardDetailArray;
    }

    /**
     * 设置房间卡牌信息
     *
     * @param $clientId
     * @param $cardArray
     *
     * @return bool
     */
    function setRoomCardArrayInRedis($clientId, $cardArray) {
        global $redis, $room;
        //房间号码
        $roomNumber = getClientIdConnectRoom($clientId);
        //设置房间
        // $room->setRoomNumber($roomNumber);
        /**
         * 在设置卡牌的位置时,应严格按照房间中每个人的位置设置
         */
        echo "房间的位置:" . $roomNumber . "PlayerPosition" . "\n";
        //如果第一次设置卡牌位置
        // if (!$redis->isKeyExistInRedis($room->getNameCardArray())) {
        //     //根据房间里的每个人位置,设置卡牌的位置
        //     $playerPosition = $redis->getHashValuesInRedis($roomNumber . "PlayerPosition");
        //     //循环玩家的位置,设置卡牌
        //     foreach ($playerPosition as $key => $item) {
        //         $redis->setHashKeysInRedis($room->getNameCardArray(), $item, "");
        //     }
        // }

        //房间中玩家位置的数组
        //roomXXXXPlayerPosition
        //p1 XXXXXXXXX(client_id)
        //p2 XXXXXXXXX(client_id)
        $playerPosition = $redis->getHashValuesInRedis($roomNumber . "PlayerPosition");
        //查询RoomXXXCardArray是否存在牌组,如果存在说明,在此之前已经有玩家发送了选牌,如果不存在表示还没有任何一个玩家发送过选牌
        $isFirstCardArrayExist = $redis->isKeyExistInRedis($roomNumber . "CardArray");

        //检查是否存在过玩家发送选牌
        if (!$isFirstCardArrayExist) { //如果之前没有存在过,分配每个链接的位置
            //循环玩家位置数组
            foreach ($playerPosition as $playerPositionName => $playerClientId) {
                $redis->setHashKeysInRedis($roomNumber . "CardArray", $playerClientId, "");
            }

            //设置redis
            $redis->setHashKeysInRedis($roomNumber . "CardArray", $clientId, json_encode($cardArray));
        } else { //已经存在过玩家发送选牌

            /**
             * =========================================================================================================
             * 加入掉线机制后的重要bug修复
             * ------------------------------------------------------
             * CardArray的数组机制是根据玩家已经发送选牌再注入数组,玩家掉线之前,有其他玩家注入了数组,
             * 但是掉线的玩家链接没有发生改变,这就导致了之前的链接残留在数组中,而没有更新
             * 这个bug已经在玩家重新登录AccountAction中处理过,每次上线前会检查玩家是否断线过,然后检查这个CardArray更新数据
             * =========================================================================================================
             */
            //设置redis
            $redis->setHashKeysInRedis($roomNumber . "CardArray", $clientId, json_encode($cardArray));
        }

        //循环玩家位置数组
        // foreach ($playerPosition as $playerPositionName => $playerClientId) {
        //     //判断已选择牌数组中牌是否存在
        //     $isExistCardArrayInPlayerSelectedCardArray = $redis->isHashKeyExistInRedis($room->getNameCardArray(), $playerClientId);
        //     if ($isExistCardArrayInPlayerSelectedCardArray) { //如果已存在牌
        //         //不做任何处理
        //     } else { //如果不存在牌
        //         if ($clientId == $playerClientId) {
        //             //设置每个玩家在房间里的位置时,必须严格按照每个玩家的位置设置
        //             //设置redis
        //             $redis->setHashKeysInRedis($room->getNameCardArray(), $playerClientId, json_encode($cardArray));
        //         } else {
        //             $redis->setHashKeysInRedis($room->getNameCardArray(), $playerClientId, "");
        //         }
        //     }
        // }

        //检查玩家数据是否写入成功
        if ($redis->isHashKeyExistInRedis($roomNumber . "CardArray", $clientId)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清除房间的当前牌组
     *
     * @param $clientId
     */
    function cleanRoomCardArray($clientId) {
        global $redis, $room;
        //房间号码
        $roomNumber = getClientIdConnectRoom($clientId);
        //设置房间
        $room->setRoomNumber($roomNumber);
        /**
         * 在设置卡牌的位置时,应严格按照房间中每个人的位置设置
         */
        //如果第一次设置卡牌位置
        // if ($redis->isKeyExistInRedis($room->getNameCardArray())) {

        $result = $redis->delKeyInRedis($room->getNameCardArray());
        echo "删除cardArray:" . $result . "\n";

        //根据房间里的每个人位置,设置卡牌的位置
        // $playerPosition = $redis->getHashValuesInRedis($roomNumber . "PlayerPosition");
        // //循环玩家的位置,设置卡牌
        // foreach ($playerPosition as $key => $item) {
        //     $redis->setHashKeysInRedis($room->getNameCardArray(), $item, "");
        //     echo "设置" . $item . "位置为空";
        // }
        // }
    }

    /**
     * 存储当前局牌的信息
     *
     * @param $clientId
     *
     * @return bool
     */
    function setRoomCardArrayIntoRedisWithCurrentRound($clientId) {
        global $redis, $room;
        //房间号码
        $roomNumber = getClientIdConnectRoom($clientId);
        //设置房间
        // $room->setRoomNumber($roomNumber);
        //查询RoomXXXCardArray是否存在牌组,如果存在说明,在此之前已经有玩家发送了选牌,如果不存在表示还没有任何一个玩家发送过选牌
        $isFirstCardArrayExist = $redis->isKeyExistInRedis($roomNumber . "CardArray");
        $newCardArrayName      = "";
        //检查是否存在过玩家发送选牌
        if ($isFirstCardArrayExist) { //如果之前没有存在过,分配每个链接的位置
            // //循环玩家位置数组
            // foreach ($playerPosition as $playerPositionName => $playerClientId) {
            //     $redis->setHashKeysInRedis($roomNumber . "CardArray", $playerClientId, "");
            // }
            //
            // //设置redis
            // $redis->setHashKeysInRedis($roomNumber . "CardArray", $clientId, json_encode($cardArray));
            $cardArrayList    = $redis->getAllHashValueAndKeyInRedis($roomNumber . "CardArray");
            $currentRound     = $redis->getKeyInRedis($roomNumber . "CurrentRound");
            $newCardArrayName = $roomNumber . "CardArray" . "Round" . $currentRound;
            foreach ($cardArrayList as $playerClientId => $playerCardArray) {
                $redis->setHashKeysInRedis($newCardArrayName, $playerClientId, $playerCardArray);
            }
        }

        if ($redis->isKeyExistInRedis($newCardArrayName)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 在给定的牌组中是否存在马牌信息
     *
     * @param $cardArray
     * @param $roomNumber
     *
     * @return boolean string
     */
    function isExistHorseCardInCardArray($cardArray, $roomNumber) {
        global $redis;
        echo "----------------------------------- 是否存在马牌的判断 --------------------------------\n";
        $isExistHorseCard = "false";
        //解析成数组
        if (!is_array($cardArray)) {
            $cardOne = json_decode($cardArray);
        }

        //查询是否存在马牌信息
        $roomIsExistHorseCard = $redis->getKeyInRedis($roomNumber . "IsExistHorse");
        $roomHorseValue       = $redis->getKeyInRedis($roomNumber . "HorseValue");
        echo "roomNumber:" . $roomNumber . "\n";
        echo "cardArray:" . json_encode($cardArray) . "\n";
        echo "IsExistHorse:" . $roomIsExistHorseCard . "\n";
        echo "HorseValue:" . $roomHorseValue . "\n";
        if ($roomIsExistHorseCard == "true") {
            if (is_array($cardOne)) {
                if (count($cardOne) == 13) {
                    foreach ($cardOne as $cardValue) {
                        if ($roomHorseValue == $cardValue) {
                            $isExistHorseCard = "true";
                        }
                    }
                } else {
                    echo "ERROR:isExistHorstCardInCardArray the cardArray length is not equal 13";
                }
            } else {
                echo "ERROR:isExistHorstCardInCardArray the cardArray is not array";
            }
        } elseif ($roomIsExistHorseCard == "false") {
            $isExistHorseCard = "false";
        }
        echo "isExistHorseCard:" . $isExistHorseCard . "\n";
        echo "----------------------------------- 是否存在马牌的判断 --------------------------------\n";
        return $isExistHorseCard;
    }

    /**
     * 是否所有玩家都已选择发牌
     *
     * @param $clientId
     *
     * @return bool
     */
    function isAllSendSelectCard($clientId) {
        global $redis, $room;
        //房间号码
        $roomNumber = getClientIdConnectRoom($clientId);
        //
        $room->setRoomNumber($roomNumber);

        $playerCardArray = $redis->getHashValuesInRedis($room->getNameCardArray());

        $isAllReady = false;

        echo "playerSendCardArray:" . json_encode($playerCardArray) . "\n";

        if (empty($playerCardArray)) {
            $isAllReady = false;
        } else {
            //获取房间最大限制数
            $roomMax = getRoomMaxWithName($roomNumber);

            echo "RoomMax:" . $roomMax . "\n";
            echo "playerSendCardArrayLength:" . count($playerCardArray) . "\n";

            // if ($roomMax == 2 && count($playerCardArray) == 2) {
            //     $isAllReady = true;
            // }
            //
            // if ($roomMax == 3 && count($playerCardArray) == 3) {
            //     $isAllReady = true;
            // }
            //
            // if ($roomMax == 4 && count($playerCardArray) == 4) {
            //     $isAllReady = true;
            // }

            $isAllSelectedCardArrayExistCard = true;
            //判断的标准应该是判断其中每个数组的牌是否完整
            foreach ($playerCardArray as $item) {
                // echo "playerSendCardArrayLength:" . count($item) . "\n";
                if (count(json_decode($item)) < 13) {
                    $isAllSelectedCardArrayExistCard = false;
                }
            }

            if ($isAllSelectedCardArrayExistCard) {
                $isAllReady = true;
            }

        }

        return $isAllReady;
    }

    /**
     *  获取房间内所有玩家的发牌
     *
     * @param $roomNumber
     *
     * @return array
     */
    function getSendSelectedCardArray($roomNumber) {
        global $redis, $room;
        $room->setRoomNumber($roomNumber);
        $playerCardArray = $redis->getHashValuesInRedis($room->getNameCardArray());

        return $playerCardArray;
    }

    /**
     * 获取历史牌局中的玩家发牌
     *
     * @param $roomNumber
     * @param $roundNumber
     *
     * @return array
     */
    function getSomeRoundSendSelectedCardArray($roomNumber, $roundNumber) {
        global $redis, $room;
        // $room->setRoomNumber($roomNumber);
        $playerCardArray = $redis->getHashValuesInRedis($roomNumber . "CardArrayRound" . $roundNumber);

        return $playerCardArray;
    }

    /**
     * 获取头道牌的特殊牌型
     */
    function getThreeCardArraySpecialType($cardArray) {
        // rsort($cardArray);
        //判断牌型
        $flag = "false";
        //牌型在全数组中的序列
        //对子在牌型中的序列
        $pairIndex = -1;
        //单张牌的值,只针对对子的情况
        $singleNumber = -1;
        //对子第一张牌的数值
        $pairFirstNumber = -1;
        $fullArray       = [
            [52, 51, 50, 49],
            [48, 47, 46, 45],
            [44, 43, 42, 41],
            [40, 39, 38, 37],
            [36, 35, 34, 33],
            [32, 31, 30, 29],
            [28, 27, 26, 25],
            [24, 23, 22, 21],
            [20, 19, 18, 17],
            [16, 15, 14, 13],
            [12, 11, 10, 9],
            [8, 7, 6, 5],
            [4, 3, 2, 1]
        ];

        $cardPositionArray = [];
        //查询每一个数字在数组中的组
        foreach ($cardArray as $key => $item) {
            foreach ($fullArray as $key1 => $item1) {
                if (in_array($item, $item1)) {
                    // echo "数组中第" . $key . "个数字确定在全数组中第" . $key1 . "组" . "<br/>";
                    $cardPositionArray[$item] = $key1;
                }
            }
        }

        //相同元素统计
        $samePositionArray = array_count_values($cardPositionArray);
        // print_r($samePositionArray);

        // print_r($cardPositionArray);

        if (count($samePositionArray) == 1) { //说明是三条
            $flag      = "threeSame";
            $pairIndex = current($cardPositionArray);
        } elseif (count($samePositionArray) == 2) { //说明是二带一,对子
            $flag = "onePair";
            if (current($samePositionArray) == 2) { //对子在开头,单张在结尾
                $pairIndex       = current($cardPositionArray);
                $singleNumber    = $cardArray[2];
                $pairFirstNumber = $cardArray[0]; //对子的第一张在开头
            } elseif (current($samePositionArray) == 1) { //单张在开头,对子在结尾
                $pairIndex       = end($cardPositionArray);
                $singleNumber    = $cardArray[0];
                $pairFirstNumber = $cardArray[1]; //对子的第一个张在中间
            }
        }

        $returnArray                    = [];
        $returnArray["flag"]            = $flag;
        $returnArray["index"]           = $pairIndex;
        $returnArray["singleNumber"]    = $singleNumber;
        $returnArray["pairFirstNumber"] = $pairFirstNumber;

        return $returnArray;
    }


