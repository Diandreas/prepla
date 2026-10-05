type Mode = 'word2def' | 'def2word' | 'gapfill' | 'dictation' | 'translation' | 'recall';
type Word = { word: string; definition: string; translation: string };
export function vocabularyOptions(word: Word, pool: Word[], mode: Mode): string[];
export function vocabularyMode(mode: Mode, options: string[]): Mode;
