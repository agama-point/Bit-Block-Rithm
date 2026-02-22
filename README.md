# Bit·Block·Rithm | (Algo·Rithm) 
### How Bitcoin Works: Bitcoin Math Playground

**Bit·Block·Rithm** is a simplified, educational playground designed to make the mathematical principles behind Bitcoin understandable and approachable.  
The project focuses on clarity and intuition rather than production-level accuracy, allowing users to explore *how Bitcoin works* through small, inspectable building blocks.

---

## Purpose

The goal of Bit·Block·Rithm is to demonstrate the core mathematical ideas of Bitcoin in a form that can be:
- easily read,
- easily modified,
- and easily experimented with.

Complex real-world parameters are intentionally reduced so that the underlying logic remains visible and comprehensible.

---

## Simplifications Used

To keep the system compact and suitable for learning, the following simplifications are applied:

### Elliptic Curve Cryptography (ECC)
- ECC operations are performed **modulo 251**.
- This small prime allows all calculations to be followed step by step.
- The focus is on understanding point addition, scalar multiplication, and key derivation.

### Hash Function
- A **simplified 16-bit hash function** is used.
- It preserves the core idea of diffusion and determinism without cryptographic strength.
- The hash is short enough to be visually inspected and compared.

### Local Node Simulation
- A minimal **local simulation of a Bitcoin node** is included.
- The simulation contains:
  - a simplified **mempool**,
  - a minimal **blockchain structure**.
- No networking, consensus, or mining difficulty adjustment is implemented.

---

## What This Project Is (and Is Not)

**This project is:**
- a mathematical playground,
- a teaching and visualization tool,
- a conceptual bridge between math and Bitcoin.

**This project is not:**
- a secure implementation,
- a full Bitcoin client,
- a replacement for real cryptographic libraries.

---

## Philosophy

> Don’t trust, verify — but first, understand.

Bit·Block·Rithm is about understanding *why* Bitcoin works before worrying about *how fast* or *how secure* it is.

---

## License

Educational use only.  
See `LICENSE` for details.

