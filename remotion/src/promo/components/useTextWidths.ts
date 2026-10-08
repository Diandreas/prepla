import { measureText } from '@remotion/layout-utils';
import { useEffect, useState } from 'react';
import { continueRender, delayRender } from 'remotion';
import { fontsReady } from '../fonts';

export type TextSpec = {
    text: string;
    fontFamily: string;
    fontSize: number;
    fontWeight: number;
    letterSpacing?: string;
    italic?: boolean;
};

/**
 * Mesure la largeur de textes une fois les polices chargées. Le rendu de l'image attend
 * la mesure (delayRender), pour qu'aucune image ne soit capturée avec une largeur estimée.
 */
export function useTextWidths(specs: TextSpec[]): number[] | null {
    const [handle] = useState(() => delayRender('Mesure des textes'));
    const [widths, setWidths] = useState<number[] | null>(null);

    useEffect(() => {
        let cancelled = false;
        fontsReady.then(() => {
            if (!cancelled) {
                setWidths(
                    specs.map(
                        (s) =>
                            measureText({
                                text: s.text,
                                fontFamily: s.fontFamily,
                                fontSize: s.fontSize,
                                fontWeight: s.fontWeight,
                                letterSpacing: s.letterSpacing,
                                additionalStyles: s.italic ? { fontStyle: 'italic' } : undefined,
                            }).width,
                    ),
                );
            }
            continueRender(handle);
        });
        return () => {
            cancelled = true;
        };
        // Les specs sont des constantes de scène.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return widths;
}
