import { useMemo, useState } from 'react';

export interface Column<T> {
    key: string;
    header: string;
    /** Render the cell. Defaults to String(row[key]). */
    cell?: (row: T) => React.ReactNode;
    /** Value used for sorting; defaults to row[key]. */
    sortValue?: (row: T) => string | number;
    className?: string;
}

interface DataTableProps<T> {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    onRowClick?: (row: T) => void;
    empty?: React.ReactNode;
}

/**
 * Lightweight, dependency-free table for the B2B back-office (lists of
 * centers / classes / students / assignments). Click-to-sort on columns that
 * provide a sortValue (or a primitive value at row[key]).
 */
export function DataTable<T>({ columns, rows, rowKey, onRowClick, empty }: DataTableProps<T>) {
    const [sortKey, setSortKey] = useState<string | null>(null);
    const [asc, setAsc] = useState(true);

    const sorted = useMemo(() => {
        if (!sortKey) return rows;
        const col = columns.find((c) => c.key === sortKey);
        if (!col) return rows;
        const val = (r: T) => col.sortValue?.(r) ?? ((r as Record<string, unknown>)[sortKey] as string | number) ?? '';
        return [...rows].sort((a, b) => {
            const av = val(a);
            const bv = val(b);
            if (av < bv) return asc ? -1 : 1;
            if (av > bv) return asc ? 1 : -1;
            return 0;
        });
    }, [rows, columns, sortKey, asc]);

    function toggleSort(key: string) {
        if (sortKey === key) {
            setAsc((p) => !p);
        } else {
            setSortKey(key);
            setAsc(true);
        }
    }

    if (rows.length === 0) {
        return (
            <div className="rounded-xl border border-border p-8 text-center text-sm text-muted-foreground">
                {empty ?? 'Aucune donnée.'}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-xl border border-border">
            <table className="w-full text-sm">
                <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                onClick={() => toggleSort(col.key)}
                                className={`cursor-pointer select-none px-4 py-2.5 font-semibold hover:text-foreground ${col.className ?? ''}`}
                            >
                                {col.header}
                                {sortKey === col.key && <span className="ml-1">{asc ? '▲' : '▼'}</span>}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {sorted.map((row) => (
                        <tr
                            key={rowKey(row)}
                            onClick={onRowClick ? () => onRowClick(row) : undefined}
                            className={onRowClick ? 'cursor-pointer hover:bg-muted/30' : ''}
                        >
                            {columns.map((col) => (
                                <td key={col.key} className={`px-4 py-2.5 ${col.className ?? ''}`}>
                                    {col.cell ? col.cell(row) : String((row as Record<string, unknown>)[col.key] ?? '')}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
