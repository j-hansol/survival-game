<?php

declare(strict_types=1);

namespace Users;

use App\ActionEnum;
use App\Game;

/**
 * 본인이 개발한 클래스에 대한 소개를 주석에 자유롭게 작성해주세요.
 * 이 예제 코드를 참고하여 본인만의 클래스를 만들어주세요.
 */
class TerrorboyUser implements \App\UserInterface
{
    private string $ment = '';
    private array $testShield = [];
    private ?int $testPX = null;
    private ?int $testPY = null;

    /**
     * 게임 화면에 표시될 플레이어 이름입니다.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Terrorboy';
    }

    /**
     * 게임 화면에 표시될 플레이어 메시지입니다.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->ment;
    }

    /**
     * * 사용자 액션
     *
     * @param \App\PlayerInfo $player_info 플레이어 정보
     * @param \App\TileInfo[][] $tile_info_table [세로y][가로x] 2차원 배열에 담긴 타일 정보
     * @return \App\ActionEnum
     */
    public function action(\App\PlayerInfo $player_info, array $tile_info_table): ActionEnum
    {
        /*
            // !테스트를 위해 플레이어 위치 고정
                $this->testPY = 3;
                $this->testPX = 6;

            // ! 테스트를 위해 쉴드 더미 만듦
            $this->testShield = array_fill(0, Game::mapRowNum(), array_fill(0, Game::mapColNum(), false));
            // ! X 위치 쉴드 더미
                //$this->testShield[3][0] = true;
                //$this->testShield[3][3] = true;
                //$this->testShield[3][9] = true;
            // ! Y 위치 쉴드 더미
                //$this->testShield[0][6] = true;
                //$this->testShield[1][6] = true;
                //$this->testShield[7][6] = true;
            // ! 대각선 쉴드 더미
                //$this->testShield[2][5] = true; // 왼쪽위
                //$this->testShield[2][7] = true; // 오른쪽위
                //$this->testShield[4][5] = true; // 왼쪽아래
                $this->testShield[4][7] = true; // 오른쪽아래
        */

        // 멘트 처리
        if ($player_info->hp <= 1) {
            if (($player_info->shield??0) > 0) {
                $this->ment = '아직 '.($player_info->shield??0).'발 남았다';
            } else {
                $this->ment = '아직은 살아있는중...';
            }
        } else {
            $ment = [
                '(드립실패)',
                '여긴어디? 난 누구?',
                '피바람이 부는구나!!',
                '히익!!!!!!!!',
                '살려주세요 ㅠ.ㅠ;',
                '허허...',
                '항암',
                '도사야 알지?',
            ];
            shuffle($ment);
            $this->ment = $ment[0]??'';
        }

        // 이동 할 곳 결정
        $move = $this->movePoint($player_info, $tile_info_table);
        /*
            d(match ($move) {
                0 => 'ActionEnum::Hold',
                1 => 'ActionEnum::Up',
                2 => 'ActionEnum::Down',
                3 => 'ActionEnum::Left',
                4 => 'ActionEnum::Right',
            });
            $this->preview($player_info, $tile_info_table);
        */

        // 이동위치 반환
        return match ($move) {
            0 => ActionEnum::Hold,
            1 => ActionEnum::Up,
            2 => ActionEnum::Down,
            3 => ActionEnum::Left,
            4 => ActionEnum::Right,
        };
    }

    /**
     * * 이동 위치 결정
     *
     * @param \App\PlayerInfo $player_info
     * @param array $tile_info_table
     * @return int
     */
    private function movePoint(\App\PlayerInfo $player_info, array $tile_info_table)
    {
        // 기본변수
        $px = ($this->testPX??$player_info->x??0); // 플레이어의 X 위치
        $py = ($this->testPY??$player_info->y??0); // 플레이어의 Y 위치
        $mx = (Game::mapColNum()??10); // 맵의 최대 X
        $my = (Game::mapRowNum()??10); // 맵의 최대 Y
        $point = ($py*$mx)+$px; // 플레이어의 현재 위치
        $around = []; // 내주변 이벤트 배열

        // 내 위치 기준으로 십자 방향으로 쉴드 체크 배열 만듦
        $cross = ['y'=>[], 'x'=>[]];
        for ($x=0; $x<$mx; $x++) {
            // 왼쪽 위
            $tlShield = ($this->testShield[$py-1][$x-1]??$tile_info_table[$py-1][$x-1]->exist_shield??false);
            if (!empty($tlShield)) {
                $cross['x'][2][] = [
                    'y'=>$py-1,
                    'x'=>($x-1),
                    'cross_point'=>-2, // 십자열 기준 거리
                    'cross_point2'=>2, // 십자열 기준 거리 - 양수화
                    'distance'=>(($mx+1)*-1), // 내위치로 부터의 거리
                    'distance2'=>($mx+1), // 내위치로 부터의 거리 - 양수화
                ];
            }

            // 오른쪽 위
            $trShield = ($this->testShield[$py-1][$x+1]??$tile_info_table[$py-1][$x+1]->exist_shield??false);
            if (!empty($trShield)) {
                $cross['x'][2][] = [
                    'y'=>$py+1,
                    'x'=>($x+1),
                    'cross_point'=>2, // 십자열 기준 거리
                    'cross_point2'=>2, // 십자열 기준 거리 - 양수화
                    'distance'=>(($mx-1)*-1), // 내위치로 부터의 거리
                    'distance2'=>($mx-1), // 내위치로 부터의 거리 - 양수화
                ];
            }

            // 십자열
            if ($px == $x) {
                continue;
            }
            $checkShield = ($this->testShield[$py][$x]??$tile_info_table[$py][$x]->exist_shield??false);
            if (!empty($checkShield)) {
                $pointX = $x-$px;
                $crossPoint = ($x-$px)*(($px <=> $x)*-1);
                $cross['x'][$crossPoint][] = [
                    'y'=>$py,
                    'x'=>$x,
                    'cross_point'=>$x-$px, // 십자열 기준 거리
                    'cross_point2'=>$crossPoint, // 십자열 기준 거리 - 양수화
                    'distance'=>$pointX, // 내위치로 부터의 거리
                    'distance2'=>$pointX*(($px <=> $x)*-1), // 내위치로 부터의 거리 - 양수화
                ];
            }
        }
        for ($y=0; $y<$my; $y++) {
            // 왼쪽 아래
            $blShield = ($this->testShield[$y+1][$px-1]??$tile_info_table[$y+1][$px-1]->exist_shield??false);
            if (!empty($blShield)) {
                $cross['x'][2][] = [
                    'y'=>$y+1,
                    'x'=>$px-1,
                    'cross_point'=>-2, // 십자열 기준 거리
                    'cross_point2'=>2, // 십자열 기준 거리 - 양수화
                    'distance'=>($mx-1), // 내위치로 부터의 거리
                    'distance2'=>($mx-1), // 내위치로 부터의 거리 - 양수화
                ];
            }

            // 오른쪽 위
            $brShield = ($this->testShield[$y+1][$px+1]??$tile_info_table[$y+1][$px+1]->exist_shield??false);
            if (!empty($brShield)) {
                $cross['x'][2][] = [
                    'y'=>$y-1,
                    'x'=>$px+1,
                    'cross_point'=>2, // 십자열 기준 거리
                    'cross_point2'=>2, // 십자열 기준 거리 - 양수화
                    'distance'=>($mx+1), // 내위치로 부터의 거리
                    'distance2'=>($mx+1), // 내위치로 부터의 거리 - 양수화
                ];
            }

            // 십자열
            if ($py == $y) {
                continue;
            }
            $checkShield = ($this->testShield[$y][$px]??$tile_info_table[$y][$px]->exist_shield??false);
            if (!empty($checkShield)) {
                $pointY = ($y*$mx)+$px;
                $crossPoint = ($y-$py)*(($py <=> $y)*-1);
                $cross['y'][$crossPoint][] = [
                    'y'=>$y,
                    'x'=>$px,
                    'cross_point'=>$y-$py, // 십자열 기준 거리
                    'cross_point2'=>$crossPoint, // 십자열 기준 거리 - 양수화
                    'distance'=>($pointY-$point), // 내위치로 부터의 거리
                    'distance2'=>($pointY-$point)*(($py <=> $y)*-1), // 내위치로 부터의 거리 - 양수화
                ];
            }
        }

        // 정렬 변경 및 십자선 거리중 가장 가까운 값 구함
        $crossX = $cross['x'];
        $crossY = $cross['y'];
        ksort($crossX);
        $crossX = array_values($crossX);
        ksort($crossY);
        $crossY = array_values($crossY);
        $crossXarray = $crossX[0]??null;
        $crossYarray = $crossY[0]??null;
        $min = min([$crossXarray[0]['cross_point2']??INF, $crossYarray[0]['cross_point2']??INF]);

        // 최소 거리에 해당하는 근거리 데이터만 만듦
        if ($min !== INF) {
            // 근거리 배열만듦
            if (count($crossX??[]) > 0) { // X축
                foreach (($crossXarray??[]) as $k=>$v) {
                    if ($min < $v['cross_point2']) {
                        continue;
                    }
                    $direction = ($v['cross_point'] <=> 0);
                    if ($direction > 0) {
                        $around[] = 4;
                    }
                    if ($direction < 0) {
                        $around[] = 3;
                    }
                }
            }
            if (count($crossY??[]) > 0) { // Y축
                foreach (($crossYarray??[]) as $k=>$v) {
                    if ($min < $v['cross_point2']) {
                        continue;
                    }
                    $direction = ($v['cross_point'] <=> 0);
                    if ($direction > 0) {
                        $around[] = 2;
                    }
                    if ($direction < 0) {
                        $around[] = 1;
                    }
                }
            }
        }

        // 이동 방향 반환
        if (count($around) > 0) {
            return array_rand(array_flip($around));
        } else {
            return mt_rand(0, 3);
        }
    }

    /**
     * * 플레이어 위치 미리보기
     *
     * @return string
     */
    private function preview(\App\PlayerInfo $player_info, array $tile_info_table)
    {
        for ($y=0; $y<(Game::mapRowNum()??10); $y++) {
            if ($y > 0) {
                echo '<br>';
            }
            for ($x=0; $x<(Game::mapColNum()??10); $x++) {
                $shield = ($this->testShield[$y][$x]??$tile_info_table[$y][$x]??false);
                if ($shield === true) {
                    if (($this->testPX??$player_info->x) == $x && ($this->testPY??$player_info->y) == $y) {
                        echo '✣';
                    } else {
                        echo '♦︎';
                    }
                } else if (($this->testPX??$player_info->x) == $x && ($this->testPY??$player_info->y) == $y) {
                    echo '◼︎';
                } else {
                    echo '﹒';
                }
            }
        }
        exit;
    }

    public function getDecisionTree() :string { return '';}
}