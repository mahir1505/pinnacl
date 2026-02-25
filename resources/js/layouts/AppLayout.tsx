import { Navigate, Outlet, Link, useLocation } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';

export default function AppLayout() {
    const { user, loading, logout } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    const isAdmin = user.role === 'admin' || user.role === 'superadmin';

    const navItems = [
        { path: '/dashboard', label: 'Dashboard' },
        { path: '/connect', label: 'Connect' },
        { path: '/history', label: 'History' },
        { path: '/settings', label: 'Settings' },
        ...(isAdmin ? [{ path: '/admin', label: 'Admin' }] : []),
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="bg-white shadow-sm border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <Link to="/dashboard" className="flex items-center text-xl font-bold text-indigo-600">
                                Pinnacl
                            </Link>
                            <div className="hidden sm:ml-8 sm:flex sm:space-x-4">
                                {navItems.map((item) => (
                                    <Link
                                        key={item.path}
                                        to={item.path}
                                        className={`inline-flex items-center px-3 py-2 text-sm font-medium rounded-md ${
                                            location.pathname.startsWith(item.path)
                                                ? 'text-indigo-600 bg-indigo-50'
                                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            {!user.is_premium && (
                                <Link
                                    to="/pricing"
                                    className="text-xs font-medium text-indigo-600 hover:text-indigo-500"
                                >
                                    Upgrade
                                </Link>
                            )}
                            {user.is_premium && (
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Premium
                                </span>
                            )}
                            <span className="text-sm text-gray-700">{user.name}</span>
                            <button
                                onClick={logout}
                                className="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
            <main className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <Outlet />
            </main>
        </div>
    );
}
