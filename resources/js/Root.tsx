import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '@/context/AuthContext';
import GuestLayout from '@/layouts/GuestLayout';
import AppLayout from '@/layouts/AppLayout';
import Login from '@/pages/Login';
import Register from '@/pages/Register';
import Dashboard from '@/pages/Dashboard';
import Connect from '@/pages/Connect';
import Score from '@/pages/Score';
import Stats from '@/pages/Stats';
import PostAnalytics from '@/pages/PostAnalytics';
import History from '@/pages/History';
import Share from '@/pages/Share';
import Settings from '@/pages/Settings';
import Pricing from '@/pages/Pricing';
import Landing from '@/pages/Landing';
import AdminDashboard from '@/pages/admin/Dashboard';
import AdminUsers from '@/pages/admin/Users';
import AdminReviews from '@/pages/admin/Reviews';

export default function Root() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <Routes>
                    {/* Public routes */}
                    <Route path="/landing" element={<Landing />} />
                    <Route path="/share/:id" element={<Share />} />

                    {/* Guest routes */}
                    <Route element={<GuestLayout />}>
                        <Route path="/login" element={<Login />} />
                        <Route path="/register" element={<Register />} />
                    </Route>

                    {/* Authenticated routes */}
                    <Route element={<AppLayout />}>
                        <Route path="/dashboard" element={<Dashboard />} />
                        <Route path="/connect" element={<Connect />} />
                        <Route path="/score/:platform" element={<Score />} />
                        <Route path="/stats/:platform" element={<Stats />} />
                        <Route path="/stats/:platform/posts" element={<PostAnalytics />} />
                        <Route path="/history" element={<History />} />
                        <Route path="/settings" element={<Settings />} />
                        <Route path="/pricing" element={<Pricing />} />

                        {/* Admin routes */}
                        <Route path="/admin" element={<AdminDashboard />} />
                        <Route path="/admin/users" element={<AdminUsers />} />
                        <Route path="/admin/reviews" element={<AdminReviews />} />
                    </Route>

                    {/* Redirect root to landing */}
                    <Route path="/" element={<Navigate to="/landing" replace />} />
                </Routes>
            </AuthProvider>
        </BrowserRouter>
    );
}
