// A multiple-choice question needs distinct, non-empty alternatives.
export function vocabularyOptions(word, pool, mode) {
    const field = mode === 'word2def' ? 'definition' : mode === 'translation' ? 'translation' : mode === 'def2word' ? 'word' : null;
    if (!field || !word[field]?.trim()) return [];
    const correct = word[field].trim();
    const seen = new Set([correct.toLocaleLowerCase()]);
    const wrong = [];
    for (const item of pool) {
        const value = item[field]?.trim();
        if (!value || seen.has(value.toLocaleLowerCase())) continue;
        seen.add(value.toLocaleLowerCase());
        wrong.push(value);
        if (wrong.length === 3) break;
    }
    return [correct, ...wrong];
}

export function vocabularyMode(mode, options) {
    return ['word2def', 'def2word', 'translation'].includes(mode) && options.length < 3 ? 'recall' : mode;
}
