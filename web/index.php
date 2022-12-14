<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$game = \App\Game::createOnce(10, 10);
$game->play();
?>
<!DOCTYPE html>
<html>
<head>
    <title>2022 MPUG 송년회 게임</title>
    <meta charset="utf-8" />
    <style>
    @font-face {
        font-family: 'CookieRunBold';
        src: url('/fonts/CookieRunBold.otf') format("opentype");
    }
    html, body {
        font-family: 'CookieRunBold';
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        background-color: #4158D0;
        background-image: linear-gradient(43deg, #4158D0 0%, #C850C0 46%, #FFCC70 100%);
    }

    .l-container {
        padding: 2em;
    }

    .c-play {
        font-family: 'CookieRunBold';
        font-size: 1rem;
        color: #fff;
        appearance: none;
        border: none;
        padding: .5em 1em;
        border-radius: 5px;
        background-size: 300% 100%;
        background-image: linear-gradient(to right, #29323c, #485563, #2b5876, #4e4376);
        box-shadow: 0 4px 15px 0 rgba(45, 54, 65, 0.75);
        cursor: pointer;
    }

    .l-outline {
        margin-top: 1em;
        display: flex;
    }

    .l-screen {
        padding: 1em;
        border-radius: 8px;
        background-color: #29323c;
        box-shadow: 0 4px 15px 0 rgba(45, 54, 65, 0.75);
    }

    .l-dashboard {
        width: 400px;
    }

    .decision-tree-wrapper {
        width: 50rem;
        height: 100%;
        background-color: rgba(0,0,0, 0.5);
        overflow: scroll;
        position: fixed;
        top: 0;
        right: -50rem;
        transition: all 0.5s;
    }

    .decision-tree-wrapper.show {
        display: block;
        right: 0;
    }

    .tree_row {
        margin-top: 1rem;
    }

    .box {
        background-color: black;
        color: white;
        padding: 0.5rem;
        font-size: 0.7rem;
    }

    .rbox {
        background-color: darkred;
        color: white;
        padding: 0.5rem;
        font-size: 0.7rem;
    }

    .shield_count {
        background-color: darkred;
        color: white;
        text-align: center;
    }

    .turn_no {
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
        padding: 0.5rem;
    }

    .js-turn {
        margin-left: 2rem;
        font-size: 3rem;
        font-weight: bold;
        color: yellow;
    }

    .hit {
        color: green;
        font-weight: bold;
    }

    .rhit {
        color: yellow;
        font-weight: bold;
    }

    .wall {
        background-color: black;
        color: white;
        padding: 0.5rem;
        font-size: 0.7rem;
    }

    .decision-tree-toggle-wrapper {
        position: fixed;
        top: 0;
        right: 0;
        padding: 1rem;
    }

    #decision-tree-toggle {
        position: relative;
        background-color: rgba(255,255,255,0.3);
        width: 2rem;
        height: 2rem;
        padding: 0.5rem;
        border-radius: 10px;
        border: 1px solid white;
        cursor: pointer;
    }

    #decision-tree-toggle .menu-line {
        width: 2rem;
        height: 4px;
        border: none;
        background-color: white;
        position: absolute;
        display: block;
        transition: all 0.5s;
    }

    #decision-tree-toggle .menu-line:nth-child(1) {
        top: 0.5rem;
        left: 0.5rem;
    }

    #decision-tree-toggle .menu-line:nth-child(2) {
        top: 50%;
        left: 0.5rem;
        margin-top: -2px;
    }

    #decision-tree-toggle .menu-line:nth-child(3) {
        bottom: 0.5rem;
        left: 0.5rem;
    }

    #decision-tree-toggle.show .menu-line:nth-child(1) {
        top: 1.4rem;
        left: 0.5rem;
        transform: rotateZ(45deg);
    }

    #decision-tree-toggle.show .menu-line:nth-child(2) {
        top: 50%;
        left: 0.5rem;
        margin-top: -2px;
        display: none;
    }

    #decision-tree-toggle.show .menu-line:nth-child(3) {
        bottom: 1.4rem;
        left: 0.5rem;
        transform: rotateZ(-45deg);
    }
    </style>
</head>
<body>

<div class="l-container">
    <button class="c-play js-play">Play</button>

    <div class="l-outline">
        <div class="l-screen js-screen"></div>
        <div class="l-dashboard js-dashboard"></div>
    </div>
</div>

<div class="decision-tree-wrapper">
    <?php
    echo $game->displayDecisionTree();
    ?>
</div>
<div class="decision-tree-toggle-wrapper">
    <div class="toggle-btn" id="decision-tree-toggle">
        <span class="menu-line"></span>
        <span class="menu-line"></span>
        <span class="menu-line"></span>
    </div>
</div>
<script type="module">
import {Game} from '/js/Game.js';

const colNum = <?=$game->col_num?>;
const rowNum = <?=$game->row_num?>;
const playDataListLog = JSON.parse('<?=json_encode($game->getPlayDataListLog())?>');

const game = new Game(
    '.js-screen',
    '.js-dashboard',
    colNum,
    rowNum,
    playDataListLog
);

document.querySelector('.js-play').addEventListener('click', () => {
    game.play();
});

document.querySelector('#decision-tree-toggle').addEventListener('click', () => {
    let tree_wrapper = document.querySelector('.decision-tree-wrapper');
    let button = document.querySelector('#decision-tree-toggle');
    if(button.classList.contains('show')) {
        button.classList.remove('show');
        tree_wrapper.classList.remove('show');
    }
    else {
        button.classList.add('show');
        tree_wrapper.classList.add('show');
    }
})
</script>

</body>
</html>