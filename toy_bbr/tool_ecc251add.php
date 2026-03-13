
<div class="panel">

<h3 class="col_ora">ESS251 calculator</h3>
Curve: y² = x³ + 7 (mod 251)
<hr>

<div class="box2">
<b class="col_gre">G </b>
| x:
<input id="gx">
y:
<input id="gy">
<br />

<b class="col_gre">k * G -> </b> k:
<input id="k" value="2">
<button id="btnMul">k × G</button>
</div>
<br />

<div class="box2">
<b class="col_gre">P </b>
x:
<input id="px">
y:
<input id="py">
<br>
<b class="col_gre">Q </b>
x:
<input id="qx">
y:
<input id="qy">
<br><br>

<button id="btnAdd">P + Q</button>
</div>


<div id="out" class="log"></div>

</div>
</div>

<script>
function logAdd(t) { $("#out").append(t + "\n"); }
function clearLog() { $("#out").text("");}

function fmt(P)
{
    if(P===null) return "O";
    return "["+P[0]+","+P[1]+"]";
}

$(function(){
    let G = ECC_PARAMS.G;

    $("#gx").val(G[0]);
    $("#gy").val(G[1]);

    $("#px").val(G[0]);
    $("#py").val(G[1]);

    $("#qx").val(G[0]);
    $("#qy").val(G[1]);


    // ---------- k * G ----------
    $("#btnMul").click(function(){
        clearLog();

        let k = parseInt($("#k").val());

        let G = [
            parseInt($("#gx").val()),
            parseInt($("#gy").val())
        ];

        log("k = " + k);
        log("G = " + fmt(G));
        let R = scalar_mult(k, G, ECC_PARAMS.a, ECC_PARAMS.p, ECC_PARAMS.n);
        logAdd("Result = " + fmt(R));

    });

    // ---------- P + Q ----------
    $("#btnAdd").click(function(){

        clearLog();

        let P = [
            parseInt($("#px").val()),
            parseInt($("#py").val())
        ];

        let Q = [
            parseInt($("#qx").val()),
            parseInt($("#qy").val())
        ];

        logAdd("P = " + fmt(P));
        logAdd("Q = " + fmt(Q));

        let R = point_adding(P, Q, ECC_PARAMS.p, ECC_PARAMS.a);
        logAdd("Result = " + fmt(R));
    });
});
</script>