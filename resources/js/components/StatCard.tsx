interface StatCardProps {
    label: string;
    value: string | number;
    change?: number;
    changePercent?: number;
}

export default function StatCard({ label, value, change, changePercent }: StatCardProps) {
    const isPositive = (change ?? 0) >= 0;

    return (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <p className="text-sm font-medium text-gray-500">{label}</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">
                {typeof value === 'number' ? value.toLocaleString() : value}
            </p>
            {change !== undefined && (
                <p className={`mt-1 text-sm font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                    {isPositive ? '+' : ''}{typeof change === 'number' ? change.toLocaleString() : change}
                    {changePercent !== undefined && (
                        <span className="ml-1 text-gray-500">
                            ({isPositive ? '+' : ''}{changePercent}%)
                        </span>
                    )}
                </p>
            )}
        </div>
    );
}
