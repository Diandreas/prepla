import type { FormDataConvertible } from '@inertiajs/core';
export function assessmentAnswers(answers: Record<string, FormDataConvertible>, exercises: { id: number }[]): Record<string, Record<string, FormDataConvertible>>;
