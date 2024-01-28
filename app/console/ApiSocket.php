<?php
define('basepath', dirname(dirname(__DIR__)));
define('Console', basepath . '/systems/console/');
define('Library', basepath . '/systems/Libs/');
include_once Console . 'WebSocket.php';
include_once Library . 'Http.php';

use Flame\Http;
use Flame\WebSocket;


class ApiSocket extends WebSocket
{
    private $http;
    private $url = 'https://qc-rex-game-center.qcuat.com/api/rex/game/open/goodway/data';
    private $header = array(
        'MERCHANT-ID: QC',
        'Content-Type: application/json'
    );
    public function onStart($server = null)
    {
        $this->http = new Http;
        $this->http->header = $this->header;
        $response = $this->http->post($this->url);
        $this->procResponse($response['contents']);
    }

    function procResponse($response = [])
    {
        $i = 0;
        if (!empty($response['data'])) {
            foreach ($response['data'] as $videoId => $records) {
                $i++;
                if ($records['roundCount'] > 0) {
                    //var_dump('VidId:' . $videoId);
                    $prevResult = null;
                    $roundResult = [];
                    $lst = [];
                    $resCount = [];
                    $scoreboard = null;
                    foreach ($records['roundRes'] as $roundNumber => $rounds) {
                        $i++;
                        if (!empty($rounds['timestamp'])) {
                            $init = array(
                                't_video_id' => $videoId,
                                'round_number' => $roundNumber,
                                'player_point' =>  $rounds['player_val'],
                                'banker_point' => $rounds['banker_val'],
                                'card_dealt_no' => $rounds['card_num'],
                                'pair_flg' => $rounds['pair'],
                                'convert_timestamp' => $this->convertDate($rounds['timestamp']),
                                'origin_timestamp' => $rounds['timestamp'],
                                'banker_win_cnt' => 0,
                                'player_win_cnt' => 0,
                                'banker_player_tie_cnt' => 0,
                                'create_date' => "NOW()",
                                'update_date' => "NOW()",
                            );
                            $pair_flg_desc = NULL;
                            if ($init['player_point'] > $init['banker_point']) {
                                $init['player_win_cnt'] = 1; // Player is higher
                                $roundResult['round_res'] = "P";
                                $roundResult['is_tie'] = 0;
                                switch ($init['pair_flg']) {
                                    case 1:
                                        $pair_flg_desc = "庄对";
                                        $roundResult['symbol'] = "P2";
                                        break;
                                    case 2:
                                        $pair_flg_desc = "闲对";
                                        $roundResult['symbol'] = "P2";
                                        break;
                                    case 3:
                                        $pair_flg_desc = "庄闲对";
                                        $roundResult['symbol'] = "P4";
                                        break;
                                    default:
                                        $pair_flg_desc = "无对子";
                                        $roundResult['symbol'] = "P1";
                                }
                            } elseif ($init['banker_point'] > $init['player_point']) {
                                $init['banker_win_cnt'] = 1; // Banker is higher
                                $roundResult['round_res'] = 'B';
                                $roundResult['is_tie'] = 0;
                                switch ($init['pair_flg']) {
                                    case 1:
                                        $pair_flg_desc = "庄对";
                                        $roundResult['symbol'] = "B2";
                                        break;
                                    case 2:
                                        $pair_flg_desc = "闲对";
                                        $roundResult['symbol'] = "B2";
                                        break;
                                    case 3:
                                        $pair_flg_desc = "庄闲对";
                                        $roundResult['symbol'] = "B4";
                                        break;
                                    default:
                                        $pair_flg_desc = "无对子";
                                        $roundResult['symbol'] = "B1";
                                }
                            } else {
                                $init['banker_player_tie_cnt'] = 1; // Player and Banker have the same value
                                $roundResult['round_res'] = $prevResult;
                                $roundResult['is_tie'] = 1;
                                switch ($init['pair_flg']) {
                                    case 1:
                                        $pair_flg_desc = "庄对";
                                        $roundResult['symbol'] = "T1";
                                        break;
                                    case 2:
                                        $pair_flg_desc = "闲对";
                                        $roundResult['symbol'] = "T3";
                                        break;
                                    case 3:
                                        $pair_flg_desc = "庄闲对";
                                        $roundResult['symbol'] = "T3";
                                        break;
                                    default:
                                        $pair_flg_desc = "无对子";
                                        $roundResult['symbol'] = "T1";
                                }
                            }
                            //var_dump($roundResult);
                            $prevResult = $roundResult['round_res'];
                            $lst[] = $roundResult;
                            $resCount[]  = array(
                                "id" => hash('MD5', $i),
                                "og_id" => $i,
                                "rd_num" => $init['round_number'],
                                "pl_point" => $init['player_point'],
                                "bk_point" => $init['banker_point'],
                                "card_dlt_no" => $init['card_dealt_no'],
                                "pair_flg" => $init['pair_flg'],
                                "pair_flg_desc" => $pair_flg_desc,
                                "player_win_cnt" => $init['player_win_cnt'],
                                "banker_win_cnt" => $init['banker_win_cnt'],
                                "bk_pr_tie_cnt" => $init['banker_player_tie_cnt'],
                                "winner_per_round" => $roundResult['round_res'],
                                "og_time" => $init['origin_timestamp'],
                                "converted_time" => $init['convert_timestamp']
                            );
                        }
                    }
                    if (count($lst) > 0) {
                        $scoreboard = $this->removeInitialConsecTies($lst);
                        print_r('[Scoreboard]:' . print_r($scoreboard, true));
                    }

                    print_r('[ResultCount]:' . print_r($resCount, true));
                }
            }
        }
    }
    function pairIdentifier($pair, $winner)
    {
    }
    function convertDate($timestamp)
    {
        $converted_date = "0000-00-00";
        if ($timestamp != "") {
            $time_val = $timestamp;
            $sec = floor($time_val / 1000);
            $converted_date = date('Y-m-d H:i:s', $sec);
        }
        return $converted_date;
    }
    //Remove initial ties without P or B win in the beginning of array res
    function removeInitialConsecTies($arrlst)
    {
        $i = 0;
        while ($i < count($arrlst) && $arrlst[$i]['is_tie'] === 1) $i++;
        return array_slice($arrlst, $i);
    }
}

$api = new ApiSocket;
$api->__init__();
$api->start();
