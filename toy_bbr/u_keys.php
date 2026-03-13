<?php if($k1 > 0): ?>
    <div class="box2">

        <div>
            PubKey (Hex): 
            <span id="pub-addr">...</span>
        </div>

        <div>
            Public point P (k1 * G): 
            <span id="pub-point">[ ?, ? ]</span>
        </div>

        <hr>

        <button id="toggle-priv-btn">
            SHOW PRIVATE KEY
        </button>

        <div id="priv-block" class="panel">

            <span class="col_ora">
                ⚠ PRIVATE KEY (k1):
            </span><br>

            <span id="k1-val" class="col_whi">
                <?= $k1 ?>
            </span>

        </div>

<?php else: ?>

    <div class="panel col_ora">

        The user does not have a PrivateKey k1 set. <br />
        Generate it using the interactive graphical tool below and then save it.

    </div>

<?php endif; ?>

</div>

<pre class="log">
User <?= htmlspecialchars($nick) ?> has active keys: <strong><?= $count ?> / 3</strong>
</pre>


<div class="box1">

<hr>

<div>

    <span>
        Change k1 (1–250):
    </span><br>

    <input type="number"
           id="new-k1"
           min="1"
           max="250">

    <button id="save-k1-btn">
        Save the new key
    </button>

    <div id="save-status"></div>

</div>

</div>


<hr />

<div class="panel flex-wrap">

    <div id="p5-holder"></div>

    <div class="flex-right">

        <div class="col_gre">
            MAX
        </div>

        <input type="range"
               id="ecc-slider"
               min="1"
               max="251"
               value="1">

        <div class="col_gre">
            MIN
        </div>

        <div>

            Private key:<br>

            <span id="key" class="col_gre">
                1
            </span>

        </div>

    </div>

</div>