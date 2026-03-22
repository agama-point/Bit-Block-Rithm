<?php

$last = $db->querySingle("SELECT MAX(utxo_time) FROM transactions");
if (!$last) $last = time();

$from = $last - 100 * 86400;

$sql = "
SELECT 
    strftime('%Y-%m-%d', utxo_time, 'unixepoch') AS day,
    COUNT(*) as cnt
FROM transactions
WHERE utxo_time >= $from
GROUP BY day
ORDER BY day ASC
";

$res = $db->query($sql);

$days = [];
$counts = [];

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $days[] = $row['day'];
    $counts[] = (int)$row['cnt'];
}

?>

<h3 class="col1">Transactions per day</h3>

<div class="box2" style="overflow-x:auto">

<canvas id="txChart"></canvas>

</div>


<script>

(function () {

const days = <?= json_encode($days) ?>;
const counts = <?= json_encode($counts) ?>;

const barMax = 15;

const marginLeft = 50;
const marginBottom = 25;
const marginTop = 25;

const width =
    Math.max(
        400,
        days.length * barMax + marginLeft + 10
    );

const height = 340;

const canvas = document.getElementById("txChart");

canvas.width = width;
canvas.height = height;

const ctx = canvas.getContext("2d");

const W = canvas.width;
const H = canvas.height;


// background

ctx.fillStyle = "#000";
ctx.fillRect(0,0,W,H);


// max

let max = 1;

for (let v of counts)
    if (v > max) max = v;


// bar width

const barW =
    Math.min(
        barMax,
        (W - marginLeft) / counts.length
    );


// grid

ctx.strokeStyle = "#222";

for (let i=0;i<5;i++) {

    let y =
        H
        - marginBottom
        - (H-marginBottom-marginTop)/5 * i;

    ctx.beginPath();
    ctx.moveTo(marginLeft,y);
    ctx.lineTo(W,y);
    ctx.stroke();
}


// bars

for (let i=0;i<counts.length;i++) {

    let val = counts[i];

    let h =
        (val / max)
        * (H - marginBottom - marginTop);

    let x =
        marginLeft
        + i * barW;

    let y =
        H
        - marginBottom
        - h;

    ctx.fillStyle = "#00ff88";

    ctx.fillRect(
        x,
        y,
        barW - 1,
        h
    );


    // count nad sloupcem

    ctx.save();

    ctx.fillStyle = "#66ffaa";
    ctx.font = "10px monospace";

    ctx.translate(
        x + 3,
        y - 2
    );

    ctx.rotate(-Math.PI/2);

    ctx.fillText(
        val,
        0,
        0
    );

    ctx.restore();

}



// labels dole

ctx.fillStyle = "#aaa";
ctx.font = "10px monospace";

for (let i=0;i<days.length;i++) {

    let d = days[i];

    let p = d.split("-");

    let txt =
        p[2] + "." + p[1];

    let x =
        marginLeft
        + i * barW
        + 3;

    let y =
        H - 2;

    ctx.save();

    ctx.translate(x,y);

    ctx.rotate(-Math.PI/2);

    ctx.fillText(
        txt,
        0,
        0
    );

    ctx.restore();

}

})();
</script>