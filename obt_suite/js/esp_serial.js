let port;
let reader;
let isConnected = false;
let onDataReceived = null; // Callback pro HTML

async function connectToSerial() {
    try {
        port = await navigator.serial.requestPort();
        let baud = 9600; 

        await port.open({ baudRate: parseInt(baud) });

        reader = port.readable.getReader();
        isConnected = true;

        // Spustíme smyčku pro čtení
        readLoop();
        
        return true; // Pro potvrzení úspěchu v HTML
    } catch(err) {
        console.error("Connect error:", err);
        return false;
    }
}

async function readLoop() {
    const decoder = new TextDecoder();
    let lineBuffer = ""; // Tady skládáme kousky textu

    while (isConnected) {
        try {
            const { value, done } = await reader.read();
            if (done) {
                if (reader) reader.releaseLock();
                break;
            }

            if (value) {
                // Dekódujeme data a přidáme je do bufferu
                lineBuffer += decoder.decode(value);

                // Dokud buffer obsahuje znak nového řádku, posíláme celé řádky pryč
                while (lineBuffer.includes("\n")) {
                    let parts = lineBuffer.split("\n");
                    let currentLine = parts.shift(); // Vezmi první řádek
                    lineBuffer = parts.join("\n");   // Zbytek vrať do bufferu

                    // Pokud máme zaregistrovaný callback, pošleme mu vyčištěný řádek
                    if (onDataReceived) {
                        onDataReceived(currentLine.trim());
                    }
                }
            }
        } catch (error) {
            console.error("Read error:", error);
            break;
        }
    }
}

async function disconnectFromSerial() {
    isConnected = false;
    try {
        if (reader) {
            await reader.cancel();
            reader.releaseLock();
            reader = null;
        }
        if (port) {
            await port.close();
            port = null;
        }
    } catch (e) {
        console.error("Disconnect error:", e);
    }
}

async function sendToSerial(data) {
    if (!isConnected || !port) return;

    try {
        const writer = port.writable.getWriter();
        const encoder = new TextEncoder();
        await writer.write(encoder.encode(data));
        writer.releaseLock();
    } catch (error) {
        console.error("Write error:", error);
    }
}