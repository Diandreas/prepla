#!/usr/bin/env python3
"""Compose la bande-son de la vidéo de présentation PrePla (60 s, 120 BPM) et ses bruitages.

Tout est synthétisé ici (aucun échantillon externe, donc aucun problème de droits) :
- 0–8 s    tension en la mineur (tic-tac d'horloge, battement de cœur, montée) ;
- 8 s      impact sur l'apparition du logo, passage en do majeur ;
- 10–11 s  trois cloches sur « Ton examen. Ton niveau. Ton parcours. » ;
- 12–42 s  groove positif do – sol – la mineur – fa ;
- 42–46 s  pause « mode examen » (tic-tac) ;
- 46–60 s  reprise, montée et résolution finale sur le logo.

Usage : python3 scripts/compose-promo-music.py   (depuis remotion/, nécessite numpy, scipy, ffmpeg)
Sorties : public/promo/music.mp3 et public/promo/sfx/*.wav

Variante de la version allemande (même partition, un ton plus haut, en ré majeur) :
    python3 scripts/compose-promo-music.py --transpose 2 --out music-de.mp3 --no-sfx
"""
from __future__ import annotations

import argparse
import json
import shutil
import subprocess
import sys
import wave
from pathlib import Path

import numpy as np
from scipy import signal

SR = 48000
BPM = 120
BEAT = 60 / BPM
BAR = 4 * BEAT
DUR = 60.0
N = int(DUR * SR)

ROOT = Path(__file__).resolve().parent.parent
OUT_DIR = ROOT / 'public' / 'promo'
SFX_DIR = OUT_DIR / 'sfx'
APP_SOUNDS = ROOT.parent / 'public' / 'sounds'

rng = np.random.default_rng(20261008)
TRANSPOSE = 0  # demi-tons ajoutés à toutes les notes (--transpose)


# ---------------------------------------------------------------- outils


def at(bar: float, beat: float = 0.0) -> float:
    """Temps (s) de la mesure `bar` (1 = début) et du temps `beat` (0..3)."""
    return (bar - 1) * BAR + beat * BEAT


def hz(midi: float) -> float:
    return 440.0 * 2 ** ((midi + TRANSPOSE - 69) / 12)


def tt(dur: float) -> np.ndarray:
    return np.arange(int(dur * SR)) / SR


def sos_filter(x: np.ndarray, kind: str, fc, order: int = 2) -> np.ndarray:
    sos = signal.butter(order, fc, kind, fs=SR, output='sos')
    return signal.sosfilt(sos, x, axis=-1)


def lp(x, fc, order=2):
    return sos_filter(x, 'lowpass', fc, order)


def hp(x, fc, order=2):
    return sos_filter(x, 'highpass', fc, order)


def bp(x, lo, hi, order=2):
    return sos_filter(x, 'bandpass', [lo, hi], order)


def fade(x: np.ndarray, a: float = 0.002, r: float = 0.01) -> np.ndarray:
    """Petites rampes d'entrée/sortie pour éviter tout clic."""
    n = x.shape[-1]
    na, nr = min(n, int(a * SR)), min(n, int(r * SR))
    e = np.ones(n)
    if na:
        e[:na] = np.linspace(0, 1, na)
    if nr:
        e[n - nr:] *= np.linspace(1, 0, nr)
    return x * e


def norm(x: np.ndarray, peak: float = 1.0) -> np.ndarray:
    m = np.max(np.abs(x))
    return x * (peak / m) if m > 0 else x


def noise(n: int) -> np.ndarray:
    return rng.standard_normal(n)


def swept_noise(dur: float, f_start: float, f_end: float, curve=None, bw_oct: float = 1.2) -> np.ndarray:
    """Bruit filtré par une bande qui glisse de f_start à f_end (filtrage dans le domaine STFT)."""
    n = int(dur * SR)
    x = noise(n)
    f, frames, z = signal.stft(x, fs=SR, nperseg=1024, noverlap=768)
    u = np.clip(frames / dur, 0, 1)
    if curve is not None:
        u = curve(u)
    fc = f_start * (f_end / f_start) ** u
    logf = np.log2(np.maximum(f, 20))[:, None]
    g = np.exp(-0.5 * ((logf - np.log2(fc)[None, :]) / (bw_oct / 2)) ** 2)
    _, y = signal.istft(z * g, fs=SR, nperseg=1024, noverlap=768)
    y = y[:n]
    return np.pad(y, (0, n - len(y)))


class Bus:
    """Piste stéréo avec placement en temps et panoramique."""

    def __init__(self):
        self.buf = np.zeros((2, N))

    def add(self, x: np.ndarray, t: float, gain: float = 1.0, pan: float = 0.0):
        if x.ndim == 1:
            left, right = np.cos((pan + 1) * np.pi / 4) * np.sqrt(2), np.sin((pan + 1) * np.pi / 4) * np.sqrt(2)
            x = np.vstack([x * left, x * right])
        i0 = int(round(t * SR))
        if i0 >= N:
            return
        if i0 < 0:
            x = x[:, -i0:]
            i0 = 0
        n = min(x.shape[1], N - i0)
        self.buf[:, i0:i0 + n] += gain * x[:, :n]


# ---------------------------------------------------------------- instruments


def kick(soft: bool = False) -> np.ndarray:
    t = tt(0.55)
    f = 46 + (165 - 46) * np.exp(-t / 0.038)
    body = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / (0.22 if soft else 0.3))
    click = hp(noise(len(t)), 1800) * np.exp(-t / 0.0022) * (0.08 if soft else 0.22)
    x = np.tanh(1.7 * (body + click)) / np.tanh(1.7)
    if soft:
        x = lp(x, 900)
    return fade(x, 0.0008, 0.05)


def clap() -> np.ndarray:
    t = tt(0.4)
    e = np.zeros_like(t)
    for d, a in [(0.0, 0.75), (0.010, 0.85), (0.021, 1.0)]:
        e += a * (t >= d) * np.exp(-np.maximum(0, t - d) / 0.0055)
    e += 0.55 * (t >= 0.028) * np.exp(-np.maximum(0, t - 0.028) / 0.085)
    x = bp(noise(len(t)), 950, 3200) * e
    x += 0.25 * bp(noise(len(t)), 3000, 9000) * np.exp(-t / 0.03)
    return fade(norm(x), 0.0005, 0.03)


def snare() -> np.ndarray:
    t = tt(0.3)
    tone = np.sin(2 * np.pi * 190 * t) * np.exp(-t / 0.05)
    rattle = bp(noise(len(t)), 1800, 8000) * np.exp(-t / 0.07)
    return fade(norm(0.6 * tone + rattle), 0.0005, 0.03)


def hat(open_: bool = False) -> np.ndarray:
    t = tt(0.35 if open_ else 0.08)
    x = hp(noise(len(t)), 7500, 4) * np.exp(-t / (0.1 if open_ else 0.016))
    return fade(norm(x), 0.0003, 0.01)


def tick(high: bool = True) -> np.ndarray:
    t = tt(0.05)
    f = 3300 if high else 2500
    x = np.sin(2 * np.pi * f * t) * np.exp(-t / 0.0065) + 0.45 * hp(noise(len(t)), 5000) * np.exp(-t / 0.0018)
    return fade(norm(x), 0.0003, 0.01)


def bass(midi: float, dur: float) -> np.ndarray:
    t = tt(dur)
    f = hz(midi)
    x = np.sin(2 * np.pi * f * t) + 0.32 * np.sin(2 * np.pi * 2 * f * t) + 0.12 * np.sin(2 * np.pi * 3 * f * t)
    e = np.minimum(1, t / 0.004) * (0.78 + 0.22 * np.exp(-t / 0.09))
    x = np.tanh(1.3 * x * e) / np.tanh(1.3)
    return fade(x, 0.003, min(0.03, dur / 3))


def pad(notes: list[int], dur: float, bright: float = 1.0, attack: float = 0.3, release: float = 0.6) -> np.ndarray:
    """Nappe chaude : voix légèrement désaccordées, harmoniques adoucies, ouverture stéréo."""
    t = tt(dur + release)
    out = np.zeros((2, len(t)))
    for m in notes:
        for det, pan in [(-8, -0.7), (0, 0.0), (8, 0.7)]:
            f = hz(m) * 2 ** (det / 1200)
            ph = rng.uniform(0, 2 * np.pi)
            voice = np.zeros_like(t)
            for k in range(1, 14):
                if k * f > 7000:
                    break
                voice += (1 / k) * np.exp(-k * 0.22 / bright) * np.sin(2 * np.pi * k * f * t + ph * k)
            lg, rg = np.cos((pan + 1) * np.pi / 4), np.sin((pan + 1) * np.pi / 4)
            out[0] += voice * lg
            out[1] += voice * rg
    env = np.minimum(1, t / attack)
    env = np.where(t > dur, np.cos(np.clip((t - dur) / release, 0, 1) * np.pi / 2) ** 2, env)
    out *= env
    # Coupe-bas : laisse la place à la basse (évite un bas-médium boueux).
    out = hp(lp(out, 1800 + 2600 * bright), 170)
    return out / (len(notes) * 2.2)


def pluck(midi: float, dur: float = 0.7) -> np.ndarray:
    t = tt(dur)
    f = hz(midi)
    x = np.zeros_like(t)
    for k in range(1, 9):
        x += (1 / k ** 1.15) * np.sin(2 * np.pi * k * f * t) * np.exp(-t * (5 + 3.2 * k))
    return fade(x * np.minimum(1, t / 0.0015), 0.0008, 0.02)


def bell(midi: float, dur: float = 2.4) -> np.ndarray:
    t = tt(dur)
    f = hz(midi)
    index = 1.8 * np.exp(-t / 0.35)
    x = np.sin(2 * np.pi * f * t + index * np.sin(2 * np.pi * 3.5 * f * t)) * np.exp(-t / 0.85)
    x += 0.35 * np.sin(2 * np.pi * 2 * f * t) * np.exp(-t / 0.4)
    return fade(x * np.minimum(1, t / 0.001), 0.0005, 0.05)


def impact() -> np.ndarray:
    t = tt(3.0)
    f = 36 + (120 - 36) * np.exp(-t / 0.12)
    boom = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.9)
    thump = lp(noise(len(t)), 260) * np.exp(-t / 0.07) * 1.6
    crash = np.vstack([bp(noise(len(t)), 2800, 12000), bp(noise(len(t)), 2800, 12000)]) * np.exp(-t / 1.1) * 0.22
    x = np.vstack([boom + thump, boom + thump]) + crash
    return fade(np.tanh(1.2 * x) / np.tanh(1.2), 0.0008, 0.3)


def crash() -> np.ndarray:
    t = tt(2.2)
    x = np.vstack([bp(noise(len(t)), 3000, 12500), bp(noise(len(t)), 3000, 12500)]) * np.exp(-t / 0.75)
    return fade(norm(x), 0.0005, 0.2)


def reverse_crash(dur: float = 1.3) -> np.ndarray:
    t = tt(dur)
    x = np.vstack([bp(noise(len(t)), 2500, 11000), bp(noise(len(t)), 2500, 11000)]) * np.exp(-(dur - t) / 0.45)
    return fade(norm(x), 0.05, 0.004)


def riser(dur: float) -> np.ndarray:
    t = tt(dur)
    u = t / dur
    sweep = swept_noise(dur, 350, 7000, curve=lambda v: v ** 1.6, bw_oct=1.4) * (u ** 2.2)
    tone_f = 180 * 2 ** (3 * u)
    tone = np.sin(2 * np.pi * np.cumsum(tone_f) / SR) * (u ** 2) * 0.18
    x = norm(sweep) + tone
    return fade(np.vstack([x, np.roll(x, 240)]) * 0.9, 0.05, 0.006)


def whoosh(dur: float = 0.55, f_lo: float = 500, f_hi: float = 5000) -> np.ndarray:
    t = tt(dur)
    u = t / dur
    x = swept_noise(dur, f_lo, f_hi, curve=lambda v: np.sin(v * np.pi), bw_oct=1.6)
    x = x * np.sin(np.pi * u) ** 1.5
    return fade(norm(np.vstack([x, np.roll(x, 180)])), 0.002, 0.01)


def key_click(variant: int) -> np.ndarray:
    t = tt(0.06)
    f = [1900, 2300, 2100][variant % 3]
    x = 0.5 * np.sin(2 * np.pi * f * t) * np.exp(-t / 0.004) + bp(noise(len(t)), 2000, 9000) * np.exp(-t / 0.006)
    x += 0.3 * lp(noise(len(t)), 600) * np.exp(-t / 0.012)
    return fade(norm(x), 0.0003, 0.01)


def reverb(x: np.ndarray, rt60: float = 1.9, predelay: float = 0.025) -> np.ndarray:
    n = int(rt60 * SR)
    t = tt(rt60)
    decay = np.exp(-6.91 * t / rt60)
    ir = np.vstack([lp(noise(n) * decay, 6500), lp(noise(n) * decay, 6500)])
    ir = np.pad(ir, ((0, 0), (int(predelay * SR), 0)))
    ir /= np.sqrt(np.sum(ir ** 2, axis=1, keepdims=True))
    wet = np.vstack([signal.fftconvolve(x[0], ir[0])[:N], signal.fftconvolve(x[1], ir[1])[:N]])
    return wet


# ---------------------------------------------------------------- partition

CHORDS = {
    'C': {'pad': [48, 60, 64, 67], 'bass': 36, 'arp': [60, 64, 67, 72, 76]},
    'G': {'pad': [43, 59, 62, 67], 'bass': 43, 'arp': [59, 62, 67, 71, 74]},
    'Am': {'pad': [45, 60, 64, 69], 'bass': 45, 'arp': [57, 60, 64, 69, 72]},
    'F': {'pad': [41, 60, 65, 69], 'bass': 41, 'arp': [57, 60, 65, 69, 72]},
    'E': {'pad': [40, 59, 64, 68], 'bass': 40, 'arp': [56, 59, 64, 68, 71]},
}
GROOVE = ['C', 'G', 'Am', 'F']


def compose():
    drums, music, send, sub = Bus(), Bus(), Bus(), Bus()
    kick_times: list[float] = []

    def hit_kick(t: float, gain: float = 1.0, soft: bool = False):
        drums.add(kick(soft), t, 0.9 * gain)
        if not soft:
            kick_times.append(t)

    # --- 1-2 : tension (la mineur), tic-tac en croches, battements de cœur
    for i in range(16):
        drums.add(tick(i % 2 == 0), i * BEAT / 2, 0.22 + 0.06 * (i % 2 == 0), pan=0.25 if i % 2 else -0.25)
    for b in range(4):
        hit_kick(at(1, b * 2) if b < 2 else at(2, (b - 2) * 2), 0.55, soft=True)
    drone = pad(CHORDS['Am']['pad'], 4.0, bright=0.45, attack=1.8, release=0.4)
    music.add(drone, 0.0, 0.75)
    sub.add(bass(33, 4.0) * np.linspace(0.2, 1, int(4.0 * SR)), 0.0, 0.32)

    # --- 3-4 : le chaos, doubles croches, montée vers l'impact
    for i in range(30):
        t = at(3) + i * BEAT / 4
        drums.add(tick(i % 2 == 0), t, 0.16 + 0.12 * i / 30, pan=0.35 if i % 2 else -0.35)
    for b in range(7):
        hit_kick(at(3) + b * BEAT, 0.5 + 0.06 * b, soft=True)
    music.add(pad(CHORDS['F']['pad'], 2.0, bright=0.6, attack=0.2), at(3), 0.7)
    music.add(pad(CHORDS['E']['pad'], 1.75, bright=0.75, attack=0.15, release=0.2), at(4), 0.75)
    sub.add(bass(29, 2.0), at(3), 0.3)
    sub.add(bass(28, 1.75), at(4), 0.3)
    music.add(riser(3.0), at(3, 2), 0.42)
    music.add(reverse_crash(1.3), at(5) - 1.3, 0.3)

    # --- 5 : impact du logo, do majeur lumineux
    drums.add(impact(), at(5), 0.95)
    kick_times.append(at(5))
    music.add(pad(CHORDS['C']['pad'] + [72], 2.0, bright=1.1, attack=0.02, release=0.8), at(5), 0.8)
    sub.add(bass(36, 2.0), at(5), 0.35)
    for k, m in enumerate([84, 91]):
        send.add(bell(m, 2.6), at(5) + 0.02 * k, 0.12, pan=-0.4 + 0.8 * k)

    # --- 6 : « Ton examen. Ton niveau. Ton parcours. » sur trois cloches
    music.add(pad(CHORDS['G']['pad'], 2.0, bright=1.0, attack=0.15), at(6), 0.62)
    sub.add(bass(43, 2.0), at(6), 0.3)
    for k, m in enumerate([76, 79, 84]):
        bl = bell(m, 2.2)
        music.add(bl, at(6, k), 0.34, pan=(-0.3, 0.0, 0.3)[k])
        send.add(bl, at(6, k), 0.3)
    for i in range(8):
        drums.add(snare(), at(6, 3) + i * BEAT / 8, 0.12 + 0.06 * i)

    # --- 7-21 : groove
    def groove_bar(bar: int, chord: str, clap_on: bool, arp_gain: float, open_hat: bool, bells: bool = False):
        c = CHORDS[chord]
        for b in range(4):
            hit_kick(at(bar, b))
            drums.add(hat(), at(bar, b + 0.5), 0.22, pan=0.3)
            if b % 2 == 1 and clap_on:
                drums.add(clap(), at(bar, b), 0.42)
                send.add(clap(), at(bar, b), 0.1)
        if open_hat:
            drums.add(hat(True), at(bar, 3.5), 0.16, pan=-0.3)
        for e in range(8):
            m = c['bass'] + (12 if e % 2 else 0)
            sub.add(bass(m, BEAT / 2 * 0.92), at(bar, e / 2), 0.36 if e % 2 == 0 else 0.26)
        music.add(pad(c['pad'], BAR, bright=0.95, attack=0.05, release=0.35), at(bar), 0.5)
        if arp_gain > 0:
            pattern = [0, 1, 2, 3, 4, 3, 2, 1, 0, 2, 3, 4, 3, 2, 1, 2]
            for s, idx in enumerate(pattern):
                note = pluck(c['arp'][idx], 0.6)
                pan = -0.45 if s % 2 else 0.45
                music.add(note, at(bar, s / 4), arp_gain * (1.0 if s % 4 == 0 else 0.72), pan=pan)
                send.add(note, at(bar, s / 4), arp_gain * 0.35)
        if bells:
            send.add(bell(c['arp'][4] + 12, 2.0), at(bar), 0.1)
            music.add(bell(c['arp'][4] + 12, 2.0), at(bar), 0.1, pan=0.2)

    for bar in range(7, 22):
        chord = GROOVE[(bar - 7) % 4]
        groove_bar(
            bar,
            chord,
            clap_on=bar >= 10,
            arp_gain=0.0 if bar < 8 else (0.16 if bar < 10 else 0.2),
            open_hat=bar >= 10 and bar % 2 == 1,
            bells=bar >= 18 and bar % 2 == 0,
        )
    for bar in (10, 14, 18):
        drums.add(crash(), at(bar), 0.22)
        send.add(crash(), at(bar), 0.06)

    # --- 22-23 : « mode examen », tic-tac sur les temps
    music.add(pad(CHORDS['Am']['pad'], 2.0, bright=0.7, attack=0.1), at(22), 0.55)
    music.add(pad(CHORDS['F']['pad'], 2.0, bright=0.75, attack=0.1), at(23), 0.55)
    sub.add(bass(45, 2.0), at(22), 0.3)
    sub.add(bass(41, 2.0), at(23), 0.3)
    for i in range(16):
        drums.add(tick(i % 2 == 0), at(22) + i * BEAT / 2, 0.26 if i % 2 == 0 else 0.16)
    hit_kick(at(22), 0.8)
    hit_kick(at(23), 0.8)
    music.add(riser(1.6), at(23, 0.8), 0.36)

    # --- 24-26 : reprise
    for bar, chord in zip(range(24, 27), ['C', 'G', 'Am']):
        groove_bar(bar, chord, clap_on=True, arp_gain=0.2, open_hat=bar % 2 == 0)
    drums.add(crash(), at(24), 0.24)
    music.add(riser(2.0), at(26), 0.4)
    for i in range(8):
        drums.add(snare(), at(26, 2) + i * BEAT / 4, 0.1 + 0.05 * i)

    # --- 27-28 : « Ne révise plus au hasard. » / « Prépare-toi avec méthode. »
    drums.add(impact(), at(27), 0.7)
    kick_times.append(at(27))
    music.add(pad(CHORDS['F']['pad'] + [72], 2.0, bright=1.0, attack=0.02), at(27), 0.62)
    sub.add(bass(41, 2.0), at(27), 0.36)
    drums.add(clap(), at(27, 2), 0.4)
    send.add(clap(), at(27, 2), 0.12)
    music.add(pad(CHORDS['G']['pad'] + [74], 2.0, bright=1.0, attack=0.05), at(28), 0.62)
    sub.add(bass(43, 2.0), at(28), 0.36)
    hit_kick(at(28), 0.8)
    for i in range(16):
        drums.add(snare(), at(28) + i * BEAT / 4, 0.05 + 0.02 * i)
    music.add(riser(2.0), at(28), 0.38)

    # --- 29-30 : logo final, résolution
    drums.add(impact(), at(29), 0.85)
    kick_times.append(at(29))
    groove_bar(29, 'C', clap_on=True, arp_gain=0.2, open_hat=True, bells=True)
    final = pad(CHORDS['C']['pad'] + [72, 76], 1.8, bright=1.1, attack=0.02, release=0.2)
    music.add(final, at(30), 0.6)
    hit_kick(at(30), 0.9)
    drums.add(crash(), at(30), 0.3)
    send.add(bell(84, 2.4), at(30), 0.16)
    music.add(bell(84, 2.4), at(30), 0.18)
    sub.add(bass(36, 1.8), at(30), 0.34)

    # --- mixage : sidechain, réverbération, master
    side = np.ones(N)
    seg = tt(0.32)
    duck = 1 - 0.55 * np.exp(-seg / 0.1)
    for k in kick_times:
        i0 = int(k * SR)
        n = min(len(seg), N - i0)
        if n > 0:
            side[i0:i0 + n] = np.minimum(side[i0:i0 + n], duck[:n])
    music.buf *= side
    sub.buf *= 0.35 + 0.65 * side

    wet = reverb(send.buf + 0.25 * music.buf)
    mix = 0.95 * drums.buf + music.buf + sub.buf + 0.55 * wet
    mix = hp(mix, 28)
    # Fin : tout s'éteint doucement sur les dernières images.
    fade_n = int(1.6 * SR)
    mix[:, N - fade_n:] *= np.cos(np.linspace(0, np.pi / 2, fade_n)) ** 2
    mix = np.tanh(1.1 * norm(mix, 0.95)) / np.tanh(1.1)
    return norm(mix, 0.89)


def write_wav(path: Path, x: np.ndarray):
    path.parent.mkdir(parents=True, exist_ok=True)
    data = np.clip(x, -1, 1)
    if data.ndim == 1:
        data = np.vstack([data, data])
    pcm = (data.T * 32767).astype('<i2')
    with wave.open(str(path), 'wb') as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(SR)
        w.writeframes(pcm.tobytes())


def loudnorm_linear(src: Path, dst: Path, target: float = -16.0, peak: float = -1.5):
    """Normalisation EBU R128 en deux passes, en mode linéaire (gain constant, dynamique intacte)."""
    probe = subprocess.run(
        ['ffmpeg', '-hide_banner', '-i', str(src), '-af', f'loudnorm=I={target}:TP={peak}:LRA=11:print_format=json', '-f', 'null', '-'],
        capture_output=True,
        text=True,
        check=True,
    ).stderr
    stats = json.loads(probe[probe.rindex('{'):probe.rindex('}') + 1])
    af = (
        f"loudnorm=I={target}:TP={peak}:LRA=11:linear=true:"
        f"measured_I={stats['input_i']}:measured_TP={stats['input_tp']}:measured_LRA={stats['input_lra']}:"
        f"measured_thresh={stats['input_thresh']}:offset={stats['target_offset']}"
    )
    subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', str(src), '-af', af, '-ar', str(SR), '-b:a', '256k', str(dst)], check=True)
    print(f"Loudness mesurée {stats['input_i']} LUFS → {target} LUFS")


def main():
    global TRANSPOSE
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument('--transpose', type=int, default=0, help='transposition en demi-tons (version allemande : 2)')
    parser.add_argument('--out', default='music.mp3', help='nom du fichier produit dans public/promo/')
    parser.add_argument('--no-sfx', action='store_true', help='ne pas régénérer les bruitages')
    args = parser.parse_args()
    TRANSPOSE = args.transpose

    if not shutil.which('ffmpeg'):
        sys.exit('ffmpeg est requis.')
    tmp = ROOT / 'out' / 'music.wav'
    write_wav(tmp, compose())
    loudnorm_linear(tmp, OUT_DIR / args.out)
    print(f'Musique : {OUT_DIR / args.out}')
    if args.no_sfx:
        return

    write_wav(SFX_DIR / 'whoosh.wav', whoosh(0.6, 400, 4800) * 0.8)
    write_wav(SFX_DIR / 'swoosh.wav', whoosh(0.32, 900, 7000) * 0.7)
    for i in range(3):
        write_wav(SFX_DIR / f'key-{i}.wav', key_click(i) * 0.5)
    for name in ['click', 'pop', 'correct', 'incorrect', 'xp', 'complete']:
        shutil.copy(APP_SOUNDS / f'{name}.mp3', SFX_DIR / f'{name}.mp3')
    print(f'Bruitages : {SFX_DIR}')


if __name__ == '__main__':
    main()
