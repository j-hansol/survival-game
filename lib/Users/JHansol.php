<?php

namespace Users;

use App\ActionEnum;
use App\Game;
use App\PlayerInfo;
use App\TileInfo;
use App\UserInterface;

/**
 * 저의 캐릭터 클래서는 가장 가까운 방어막으로 이동하도록 합니다.
 * 방어막이 인접하여 1개 이상인 경우 임의의 하나를 선택하여 해당 방향으로 이동합니다. 인접한 방어막이 없는 경우에는
 * 이동 가능한 빈 공간 중 방어막이 가장 가까운 것을 선택하여 이동합니다.
 * 가끔 우왕좌왕하는 경우가 있는데, 화면에는 보호막이 없는데, 타일에는 보호막이 존재하는 것으로 나오는 경우가 있는 듯 합니다.
 * 이 것을 회피하도록 만들려다 여기까지만 하겠습니다. ^^
 */
class JHansol implements UserInterface {
    private const UP        = 0;
    private const DOWN      = 1;
    private const LEFT      = 2;
    private const RIGHT     = 3;
    private const NONE      = -1;

    private $default_decision_data = [
        'movable' => false,
        'is_shield' => false,
        'distance' => 999,
        'direction' => false
    ];
    private $name = 'j-Hansol';

    public $decision_tree = [];
    public $arround_data = [];
    public $shield_count = [];

    public function getName(): string {
        return $this->name;
    }

    public function action(PlayerInfo $playerInfo, array $tile_info_table): ActionEnum {
        // 이동 가능 타일 점검
        // 총 4개 중 타일의 가장자리에서 이동을 체크한 후 이동 가능하면 타일 정보를 그렇지 않으면 Null 지정
        $t = [
            self::UP => $playerInfo->y == 0 ? null : $tile_info_table[$playerInfo->y - 1][$playerInfo->x],
            self::DOWN => Game::mapRowNum() - 1 == $playerInfo->y ? null : $tile_info_table[$playerInfo->y + 1][$playerInfo->x],
            self::LEFT => $playerInfo->x == 0 ? null : $tile_info_table[$playerInfo->x - 1][$playerInfo->y],
            self::RIGHT => Game::mapColNum() - 1 == $playerInfo->x ? null : $tile_info_table[$playerInfo->x + 1][$playerInfo->y]
        ];
        $this->arround_data[] = $t;

        // 가장 가까운 방어막을 추출하기 위한 데이터 작성
        $AllShields = [];
        foreach($tile_info_table as $row_id => $cols) {
            foreach($cols as $col_id => $info) {
                if($info->exist_shield && !$info->exist_player) {
                    $AllShields[] = [
                        'x' => $col_id,
                        'y' => $row_id,
                    ];
                }
            }
        }

        $this->shield_count[] = count($AllShields);

        // 이동 가능한 방어막 타일
        $shields = [];
        // 이동 가능한 빈 공간 타일
        $blanks = [];
        // 이동 방향
        $direction = self::NONE;

        $t2 = [
            self::UP => $this->default_decision_data,
            self::DOWN => $this->default_decision_data,
            self::LEFT => $this->default_decision_data,
            self::RIGHT => $this->default_decision_data
        ];

        // 4개의 타일중 이동 가능한 타일정보 추출
        foreach($t as $idx => $val) {
            if($val) {
                if($val->exist_shield && !$val->exist_player) {
                    $shields[] = $idx;
                    $t2[$idx]['movable'] = $t2[$idx]['is_shield'] = true;
                }
                else if(!$val->exist_player) {
                    $blanks[] = [
                        // 방향 정보와 방어막까지의 거리를 계산하기 위한 좌표를 포함하여 빈공간 정보를 저장
                        'idx' => $idx,
                        'x' => $playerInfo->x + (self::LEFT == $idx ? -1 : (self::RIGHT == $idx ? 1 : 0)),
                        'y' => $playerInfo->y + (self::UP == $idx ? -1 : (self::DOWN == $idx ? 1 : 0))
                    ];
                    $t2[$idx]['movable'] = true;
                    $t2[$idx]['is_shield'] = false;
                }
            }
            else {
                $t2[$idx]['movable'] = false;
                $t2[$idx]['distance'] = 999;
            }
        }

        // 이동 가능한 바어막 타일이 있는 경우 바어막 중 임의의 하나 선택 방어막이 없고 빈공간이 있는 경우 바어막과 가장 가까운 빈공간을 선택
        // 한다. 그렇지 않으면 4방향 중 임의의 방향 선택
        $direction_t = $this->getNearestShield($blanks, $AllShields, $t2);
        if(count($shields) > 0) $direction = $shields[mt_rand(0, count($shields) - 1)];
        else if(count($blanks) > 0) $direction = $direction_t;
        else $direction = mt_rand(0, 3);

        $t2[$direction]['direction'] = true;
        $this->decision_tree[] = $t2;

        return match ($direction) {
            0 => ActionEnum::Up,
            1 => ActionEnum::Down,
            2 => ActionEnum::Left,
            3 => ActionEnum::Right,
        };
    }

    /**
     * 이동 가능 빈공간과 방어막과 가장 가까운 공간의 방향을 선택하여 리턴한다.
     * @param $blanks
     * @param $shieldInfos
     * @return int
     */
    private function getNearestShield($blanks,  $shieldInfos, &$decision_data) : int {
        $nearestShield2 = null;
        foreach( $blanks as $blank) {
            if(!$decision_data[$blank['idx']]['movable']) continue;
            $nearestShield = null;
            foreach( $shieldInfos as $info) {
                $dist = sqrt(pow($info['x'] - $blank['x'], 2) + pow($info['y'] - $blank['y'], 2));
                if(!$nearestShield) {
                    $nearestShield = [
                        'x' => $info['x'],
                        'y' => $info['y'],
                        'dist' => $dist
                    ];
                }
                else if($nearestShield['dist'] > $dist) {
                    $nearestShield = [
                        'x' => $info['x'],
                        'y' => $info['y'],
                        'dist' => $dist
                    ];
                }
            }
            if(count($shieldInfos) == 0) $decision_data[$blank['idx']]['distance'] = 999;
            else $decision_data[$blank['idx']]['distance'] = $nearestShield['dist'];

            if(!$nearestShield2) $nearestShield2 = [
                'idx' => $blank['idx'],
                'dist' => $nearestShield['dist']
            ];
            else if($nearestShield2['dist'] > $nearestShield['dist']) $nearestShield2 = [
                'idx' => $blank['idx'],
                'dist' => $nearestShield['dist']
            ];
        }


        return $nearestShield2['idx'];
    }

    public function getMessage(): string {
        $msg_list = [
            '내가 승자다.',
            '보호막!!?? 방어막 어딨어?',
            '보호막으로 고고씽~~',
            '마지막까지 살아 남을꺼야 난~~~',
            '나는 보호막 기습부대',
            '난! 폭탄 실어!!!'
        ];
        shuffle($msg_list);
        return $msg_list[0];
    }

    public function getDecisionTree() : string
    {
        $str = '<table>';

        for($i = 0 ; $i < count($this->arround_data) ; $i++) {
            if(count($this->decision_tree) <= $i) continue;
            $str .= "<tr class=\"tree_row\"><td class=\"turn_no\">{$i}</td><td>";
            $str .= '<table><tr><td> </td><td>' . $this->convert2($this->arround_data[$i][self::UP]) . '</td><td> </td></tr>';
            $str .= '<tr><td>' . $this->convert2($this->arround_data[$i][self::LEFT]) . '</td><td> </td>';
            $str .= '<td>' . $this->convert2($this->arround_data[$i][self::RIGHT]) . '</td></tr>';
            $str .= '<tr><td> </td><td>' . $this->convert2($this->arround_data[$i][self::DOWN]) . '</td><td> </td></tr></table></td><td>';
            $str .= '<table><tr><td> </td><td>' . $this->convert($this->decision_tree[$i][self::UP]) . '</td><td> </td></tr>';
            $str .= '<tr><td>' . $this->convert($this->decision_tree[$i][self::LEFT]) . '</td><td class="shield_count"> 보호막 수 : ' . $this->shield_count[$i] . '</td>';
            $str .= '<td>' . $this->convert($this->decision_tree[$i][self::RIGHT]) . '</td></tr>';
            $str .= '<tr><td> </td><td>' . $this->convert($this->decision_tree[$i][self::DOWN]) . '</td><td> </td></tr></table></td></tr>';
        }
        return $str . '</table>';
    }

    private function convert($info) : string {
        if(!is_array($info)) return '';

        $t = '<div class="box">이동 가능 여부 : ' . ($info['movable'] ? '가능' : '불가능') . '</br>';
        $t .= '보호막 : ' . ($info['is_shield'] ? '보호막' : '일반') . '</br>';
        $t .= '보호막과의 거리 : ' . number_format($info['distance'], 1) . '</br>';
        $t .= '최종 이동 결정 : ' . ($info['direction'] ? '이동' : '') . '</br></div>';
        return $t;
    }
    private function convert2($info) : string {
        if(!is_object($info)) return '<div class="wall">벽</div>';
        $t = '<div class="rbox">보호막 존재 : ' . ($info && $info->exist_shield ? '<span class="hit">존재</span>' : ' ') . '</br>';
        $t .= '다른 플레이어 존재 : ' .  ($info && $info->exist_player ? '<span class="rhit">존재</span>' : ' ') . '</br></div>';
        return $t;
    }
}