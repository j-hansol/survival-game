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

    public function getName(): string {
        return 'j-Hansol';
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

        // 가장 가까운 방어막을 추출하기 위한 데이터 작성
        $AllShields = [];
        foreach($tile_info_table as $row_id => $cols) {
            foreach($cols as $col_id => $info) {
                if($info->exist_shield) {
                    $AllShields[] = [
                        'x' => $col_id,
                        'y' => $row_id,
                    ];
                }
            }
        }

        // 이동 가능한 방어막 타일
        $shields = [];
        // 이동 가능한 빈 공간 타일
        $blanks = [];
        // 이동 방향
        $direction = self::NONE;

        // 4개의 타일중 이동 가능한 타일정보 추출
        foreach($t as $idx => $val) {
            if($val) {
                if($val->exist_shield && !$val->exist_player) $shields[] = $idx;
                else if(!$val->exist_player) $blanks[] = [
                    // 방향 정보와 방어막까지의 거리를 계산하기 위한 좌표를 포함하여 빈공간 정보를 저장
                    'idx' => $idx,
                    'x' => $playerInfo->x + (self::LEFT == $idx ? -1 : (self::RIGHT == $idx ? 1 : 0)),
                    'y' => $playerInfo->y + (self::UP == $idx ? -1 : (self::DOWN == $idx ? 1 : 0))
                ];
            }
        }

        // 이동 가능한 바어막 타일이 있는 경우 바어막 중 임의의 하나 선택 방어막이 없고 빈공간이 있는 경우 바어막과 가장 가까운 빈공간을 선택
        // 한다. 그렇지 않으면 4방향 중 임의의 방향 선택
        if(count($shields) > 0) $direction = $shields[mt_rand(0, count($shields) - 1)];
        else if(count($blanks) > 0) $direction = $this->getNearestShield($blanks, $AllShields);
        else $direction = mt_rand(0, 3);

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
    private function getNearestShield($blanks,  $shieldInfos) : int {
        $nearestShield2 = null;
        foreach( $blanks as $blank) {
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
}