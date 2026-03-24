let port;
let reader;
let isConnected = false;

// Definujeme proměnnou, do které si uložíme funkci zvenčí
let onDataReceived = null; 

async function readLoop() {
  const decoder = new TextDecoder();

  while (isConnected) {
    try {
      const { value, done } = await reader.read();
      if (done) {
        reader.releaseLock();
        break;
      }

      if (value && onDataReceived) {
        // Místo logování zavoláme callback funkci
        const text = decoder.decode(value);
        onDataReceived(text); 
      }
    } catch (error) {
      console.error("Read error:", error);
      break;
    }
  }
}


async function connectToSerial()
{
  try
  {
    port = await navigator.serial.requestPort();

    let baud = document.querySelector( 'input[name="baud"]:checked' ).value;
    log(baud);

    await port.open({ baudRate: parseInt(baud) });

    reader = port.readable.getReader();
    isConnected = true;

    $("#status").text("Připojeno");
    log("OK connected");
    readLoop();
  }
  catch(err)
  { log("ERROR: " + err); }
}


async function readLoop()
{
  const decoder = new TextDecoder();

  while(isConnected)
  {
    const { value, done } =  await reader.read();
    if(done) break;

    if(value)
    {
      let text = decoder.decode(value);
      log(text.trim());
    }
  }
}


async function disconnectFromSerial()
{
  isConnected = false;

  try
  {
    if(reader)
    {
      await reader.cancel();
      reader.releaseLock();
    }

    if(port) { await port.close(); }
  }
  catch(e)
  { log("Close error"); }

  $("#status").text("Odpojeno");
  log("Disconnected");
}



async function sendToSerial(data)
{
  if (!isConnected || !port)
  {
    log("Not connected");
    return;
  }

  try
  {
    const writer = port.writable.getWriter();
    const encoder = new TextEncoder();
    await writer.write( encoder.encode(data) );

    writer.releaseLock();
    //log("TX: " + data.trim());
  }
  catch (error) { log("Write error"); }
}