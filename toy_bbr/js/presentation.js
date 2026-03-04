// js/presentation.js

class Presentation {
    constructor(blocks, actions, blockDelay) {
        this.blocks = blocks;
        this.actions = actions;
        this.blockDelay = blockDelay;
        this.currentBlock = 0;
    }

    resetBlocks() {
        this.blocks.forEach(block => $(block).hide().css({ opacity: 1 })); // Skryje všechny bloky a resetuje opacitu
        this.currentBlock = 0;
    }

    nextStep() {
        const action = this.actions[this.currentBlock];
        const $block = $(this.blocks[this.currentBlock]);
        const $prevBlock = this.currentBlock > 0 ? $(this.blocks[this.currentBlock - 1]) : null;

        if (action === "top" || action === "left") {
            if ($prevBlock) $prevBlock.hide();
            const animationProps = action === "top" ? { top: "0" } : { left: "0" };
            $block.css({
                top: action === "top" ? "-100px" : "0",
                left: action === "left" ? "-100px" : "0",
                opacity: 1,
                "z-index": 2
            }).show().animate(animationProps, 1000);
        } else if (action === "alfa") {
            if ($prevBlock) {
                $prevBlock.css({ "z-index": 1, opacity: 1 }).animate({ opacity: 0 }, 1000);
            }
            $block.css({ "z-index": 2, opacity: 0 }).show().animate({ opacity: 1 }, 1000);
        }

        this.currentBlock++;
    }

    prevStep() {
        if (this.currentBlock > 0) {
            this.currentBlock--; // Sníží index pro předchozí blok
            const action = this.actions[this.currentBlock];
            const $block = $(this.blocks[this.currentBlock]);
            const $nextBlock = this.currentBlock < this.blocks.length - 1 ? $(this.blocks[this.currentBlock + 1]) : null;

            if ($nextBlock) $nextBlock.hide(); // Skryje následující blok

            if (action === "top" || action === "left") {
                const animationProps = action === "top" ? { top: "-100px" } : { left: "-100px" };
                $block.show().css(animationProps).animate({ top: "0", left: "0" }, 1000);
            } else if (action === "alfa") {
                $block.css({ "z-index": 2, opacity: 0 }).show().animate({ opacity: 1 }, 1000);
            }
        }
    }

    showNextBlock() {
        this.nextStep();

        if (this.currentBlock >= this.blocks.length) {
            if ($('#endless').is(':checked')) {
                setTimeout(() => {
                    this.resetBlocks(); // Skryje všechny bloky před restartem
                    this.showNextBlock(); // Znovu spustí první krok po resetu
                }, this.blockDelay); // Prodleva mezi posledním blokem a restartem cyklu
            }
        } else {
            // Přechod na další blok po určité prodlevě
            setTimeout(() => this.showNextBlock(), this.blockDelay);
        }
    }

    start() {
        this.resetBlocks(); // Skryje všechny bloky před spuštěním
        this.showNextBlock();
    }

    next() {
        if (this.currentBlock >= this.blocks.length) {
            this.resetBlocks(); // Pokud jsme na konci, resetuje všechny bloky
        }
        this.nextStep(); // Provádí jeden krok bez prodlevy
    }

    prev() {
        this.prevStep(); // Provádí jeden krok zpět
    }
}
