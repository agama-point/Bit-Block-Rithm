<h3 class="col_ora">ESS251 – Complete Signing and Verification</h2>
Private key:
<input type="number" id="input_key" value="111">
Message:
<input type="text" id="input_msg" value="83ca">
<button id="runBtn">Run</button>
<pre class="logx" id="demoSign"></pre>

<script>
function hex24(n) {
  return "0x" + n.toString(16).padStart(6,'0');
}

function log(txt){  $("#demoSign").append(txt + "\n");}
function logB(txt){ $("#demoSign").append("<span class='col_ora'>" + txt + "</span>\n"); }

function section(title){
  log("\n====================================================");
  log(title);
  log("====================================================");
}

$("#runBtn").click(function(){
  $("#demoScr").text("");

  let priv = parseInt($("#input_key").val());
  let msg  = $("#input_msg").val();

  if (!msg) {
    log("❌ Enter message.");
    return;
  }

  section("1) PUBLIC KEY GENERATION");

  log("Function: scalar_mult(priv, G_POINT)");
  //log("Input:");
  log("  priv = " + priv);
  let pub = scalar_mult(priv, G_POINT);  
  log("  G_POINT = [" + G_POINT + "]");  

  //log("Output:");
  log("->  public point P = [" + pub + "]");
  //log("  x = " + pub[0]);
  //log("  y = " + pub[1]);

  log("\nFunction: pubkey_to_addr(pub)");
  //log("Input:");
  //log("  pub = [" + pub + "]");

  let pub_hex = pubkey_to_addr(pub);

  //log("Output:");
  log("->  public key hex = <span class='col_ora'>" + pub_hex + "</span>");

  section("2) MESSAGE HASH");

  log("Function: ASH24(input_msg)");
  //log("Input:");
  log('input_msg = "' + msg + '"');

  let h_raw = ASH24(msg);
  let hmsg  = hex24(h_raw);

  //log("Output:");
  log("->  ASH24(msg) = " + h_raw + "  |  hex24(...) = " + hmsg);
  //log("  hex24(...) = " + hmsg);



  section("3) MESSAGE SIGNING");

  log("Function: signToy(priv, h_raw)");
  //log("Input:");
  log("  priv = " + priv + "  |  h_raw = " + h_raw);

  //log("  h_raw = " + h_raw);

  let sig = signToy(priv, h_raw);

  //log("Output object:");
  //log("  sig.R_point = [" + sig.R_point + "]");
  //log("  sig.r = " + sig.r);
  //log("  sig.s = " + sig.s);

  log("Function: signToy(priv, h_hex)");
  //log("Input:");
  //log("  priv = " + priv);
  log("  h_rhex = " + hmsg);

  log(" ");
  sig = signToy(priv, hmsg);

  log("Output object:");
  log("  sig.R_point = [" + sig.R_point + "]");
  log("  sig.r = " + sig.r);
  log("  sig.s = <span class='col_ora'>" + sig.s + "</span>");

  let sigHex = sig_to_hexa(sig); 
  //log("  sigHex = " + sigHex);
  log("  sigHex = <span class='col_ora'>" + sigHex + "</span>");

  log("\nIntermediate steps (can be verified manually):");

  let L = scalar_mult(sig.s, G_POINT);
  log("  LEFT = scalar_mult(s, G) = [<span class='col_ora'>" + L + "</span>]");

  let e_Pub = scalar_mult(h_raw % ORDER_N, pub);
  let e = (h_raw % ORDER_N);
  log("  e = " + e + "  |  pub [" + pub + "]");
  log("  e * Pub = scalar_mult(h mod n, Pub) = [<span class='col_ora'>" + e_Pub + "</span>]");

  let P = point_adding(sig.R_point, e_Pub);
  log("  RIGHT = R + e*Pub = [" + P + "]");
  log("-> ? LEFT = RIGHT ?");


  section("4) SIGNATURE VERIFICATION");

  log("Function: verifyToy(pub, h_raw, sig)");
  log("Input:");
  log("  pub = [" + pub + "]");
  log("  h_raw = " + h_raw);
  log("  sig = { r:" + sig.r + ", s:" + sig.s + " }");

  let valid = verifyToy(pub, h_raw, sig);

  log("Output:");
  log("  valid = " + valid);

  log("\nWhy is the signature valid?");
  log("  The signature is valid if:");
  log("     s*G = R + e*Pub");
  log("  which means the signature could only be created");
  log("  by the holder of the private key.");
  log("  Here:");
  log("     LEFT = [" + L + "]");
  log("    RIGHT = [" + P + "]");

  if(valid){
    log("\n  => L == P  →  Signature is VALID ✅");
  } else {
    log("\n  => L != P  →  Signature is INVALID ❌");
  }

  section("END OF PROCESS");

});

</script>