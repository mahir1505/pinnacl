import { useState, useEffect } from 'react';
import { socialApi, type SocialAccount } from '@/api/social';
import { useAuth } from '@/context/AuthContext';

const platformIcons: Record<string, string> = {
    instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
    tiktok: 'bg-black',
    youtube: 'bg-red-600',
    x: 'bg-black',
    linkedin: 'bg-blue-700',
};

const platformLabels: Record<string, string> = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    x: 'X (Twitter)',
    linkedin: 'LinkedIn',
};

export default function Connect() {
    const { user } = useAuth();
    const [accounts, setAccounts] = useState<SocialAccount[]>([]);
    const [loading, setLoading] = useState(true);
    const [connecting, setConnecting] = useState<string | null>(null);
    const [error, setError] = useState('');

    const allPlatforms = ['instagram', 'tiktok', 'youtube', 'x', 'linkedin'];

    useEffect(() => {
        loadAccounts();
    }, []);

    const loadAccounts = async () => {
        try {
            const res = await socialApi.getAccounts();
            setAccounts(res.data);
        } catch {
            setError('Failed to load accounts.');
        } finally {
            setLoading(false);
        }
    };

    const handleConnect = async (platform: string) => {
        setConnecting(platform);
        setError('');
        try {
            const res = await socialApi.connect(platform);
            window.location.href = res.data.redirect_url;
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { message?: string } } };
            setError(axiosError.response?.data?.message || 'Failed to connect.');
            setConnecting(null);
        }
    };

    const handleDisconnect = async (id: number) => {
        try {
            await socialApi.disconnect(id);
            setAccounts((prev) => prev.filter((a) => a.id !== id));
        } catch {
            setError('Failed to disconnect.');
        }
    };

    const handleSync = async (id: number) => {
        try {
            await socialApi.sync(id);
            await loadAccounts();
        } catch {
            setError('Failed to sync.');
        }
    };

    const connectedPlatforms = accounts.map((a) => a.platform);
    const canConnectMore = user?.is_premium || accounts.length < 1;

    if (loading) {
        return (
            <div className="flex justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Connect Platforms</h1>
                <p className="mt-1 text-sm text-gray-500">
                    Connect your social media accounts to get your Pinnacl score.
                    {!user?.is_premium && (
                        <span className="text-amber-600 ml-1">
                            Free plan: 1 platform. Upgrade for all 5.
                        </span>
                    )}
                </p>
            </div>

            {error && (
                <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {error}
                </div>
            )}

            {/* Connected accounts */}
            {accounts.length > 0 && (
                <div className="mb-8">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Connected</h2>
                    <div className="space-y-3">
                        {accounts.map((account) => (
                            <div
                                key={account.id}
                                className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between"
                            >
                                <div className="flex items-center space-x-4">
                                    <div className={`w-10 h-10 rounded-lg ${platformIcons[account.platform]} flex items-center justify-center`}>
                                        <span className="text-white text-sm font-bold">
                                            {platformLabels[account.platform]?.[0]}
                                        </span>
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {account.platform_name}
                                        </p>
                                        <p className="text-sm text-gray-500">@{account.username}</p>
                                    </div>
                                    {account.latest_score && (
                                        <div className="ml-4 px-3 py-1 bg-indigo-50 rounded-full">
                                            <span className="text-sm font-semibold text-indigo-700">
                                                Score: {account.latest_score.overall_score}/100 ({account.latest_score.grade})
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <div className="flex items-center space-x-2">
                                    <button
                                        onClick={() => handleSync(account.id)}
                                        className="px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md"
                                    >
                                        Sync
                                    </button>
                                    <button
                                        onClick={() => handleDisconnect(account.id)}
                                        className="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-md"
                                    >
                                        Disconnect
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Available platforms */}
            <div>
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Available Platforms</h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {allPlatforms
                        .filter((p) => !connectedPlatforms.includes(p))
                        .map((platform) => (
                            <div
                                key={platform}
                                className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center"
                            >
                                <div className={`w-14 h-14 rounded-xl ${platformIcons[platform]} flex items-center justify-center mx-auto mb-4`}>
                                    <span className="text-white text-lg font-bold">
                                        {platformLabels[platform]?.[0]}
                                    </span>
                                </div>
                                <h3 className="font-medium text-gray-900 mb-3">{platformLabels[platform]}</h3>
                                <button
                                    onClick={() => handleConnect(platform)}
                                    disabled={!canConnectMore || connecting === platform}
                                    className="w-full px-4 py-2 text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {connecting === platform ? 'Connecting...' : 'Connect'}
                                </button>
                                {!canConnectMore && (
                                    <p className="mt-2 text-xs text-amber-600">Premium required</p>
                                )}
                            </div>
                        ))}
                </div>
            </div>
        </div>
    );
}
