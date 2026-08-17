'use client';

import type { ReactNode, TableHTMLAttributes } from 'react';

export interface Column<T> {
    key: string;
    header: ReactNode;
    render: (row: T) => ReactNode;
    className?: string;
    headerClassName?: string;
}

export interface TableProps<T> extends TableHTMLAttributes<HTMLTableElement> {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    empty?: ReactNode;
    onRowClick?: (row: T) => void;
}

export function Table<T>({ columns, rows, rowKey, empty = 'No rows.', onRowClick, className = '', ...props }: TableProps<T>) {
    if (rows.length === 0) {
        return (
            <div className="nx-empty">
                <span>{empty}</span>
            </div>
        );
    }

    return (
        <div className="nx-table-wrap">
            <table className={`nx-table ${className}`} {...props}>
                <thead>
                    <tr>
                        {columns.map((col) => (
                            <th key={col.key} className={`nx-th ${col.headerClassName ?? ''}`}>
                                {col.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={rowKey(row)}
                            onClick={onRowClick ? () => onRowClick(row) : undefined}
                            style={onRowClick ? { cursor: 'pointer' } : undefined}
                        >
                            {columns.map((col) => (
                                <td key={col.key} className={`nx-td ${col.className ?? ''}`}>
                                    {col.render(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
