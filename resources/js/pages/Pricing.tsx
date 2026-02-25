import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import api from '@/api/client';

const plans = [
    {
        name: 'Free',
        price: '0',
        features: [
            '1 social media platform',
            'Score calculation 1-2x per week',
            'Top 3 tips per category',
            'Account-level statistics',
            'Last 30 days score history',
            'Shareable score card (with branding)',
        ],
        notIncluded: [
            'All 5 platforms',
            'Daily score updates',
            'Individual post tracking',
            'Unlimited score history',
            'Personal admin reviews',
            'PDF export',
        ],
        cta: 'Current Plan',
        ctaStyle: 'bg-gray-100 text-gray-700 cursor-default',
    },
    {
        name: 'Premium',
        price: '14',
        popular: true,
        features: [
            'All 5 social media platforms',
            'Daily score calculation',
            'All tips (every category)',
            'Account-level statistics',
            'Individual post tracking & metrics',
            'Unlimited score history',
            'Shareable score card (no branding)',
            'Personal admin reviews (monthly)',
            'Competitor comparison',
            'PDF export',
        ],
        notIncluded: [],
        cta: 'Upgrade to Premium',
        ctaStyle: 'bg-indigo-600 text-white hover:bg-indigo-700',
    },
];

export default function Pricing() {
    const { user } = useAuth();
    const [upgrading, setUpgrading] = useState(false);
    const [message, setMessage] = useState<string | null>(null);

    const handleUpgrade = async (plan: 'monthly' | 'yearly') => {
        setUpgrading(true);
        setMessage(null);
        try {
            const res = await api.post('/premium/checkout', { plan });
            setMessage(res.data.message);
            // Reload to refresh user state
            setTimeout(() => window.location.reload(), 1500);
        } catch {
            setMessage('Failed to upgrade. Please try again.');
        } finally {
            setUpgrading(false);
        }
    };

    return (
        <div>
            <div className="text-center mb-12">
                <h1 className="text-3xl font-bold text-gray-900">Simple, Transparent Pricing</h1>
                <p className="mt-3 text-lg text-gray-500">
                    Choose the plan that fits your needs. Upgrade or downgrade anytime.
                </p>
            </div>

            {message && (
                <div className="mb-8 max-w-4xl mx-auto p-4 rounded-md bg-green-50 text-green-700 text-center">
                    {message}
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {plans.map((plan) => (
                    <div
                        key={plan.name}
                        className={`bg-white rounded-lg shadow-sm border-2 p-8 relative ${
                            plan.popular ? 'border-indigo-500' : 'border-gray-200'
                        }`}
                    >
                        {plan.popular && (
                            <span className="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-600 text-white">
                                Most Popular
                            </span>
                        )}
                        <div className="text-center">
                            <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                            <div className="mt-4">
                                <span className="text-4xl font-bold text-gray-900">EUR {plan.price}</span>
                                <span className="text-gray-500">/month</span>
                            </div>
                        </div>

                        <ul className="mt-8 space-y-3">
                            {plan.features.map((feature) => (
                                <li key={feature} className="flex items-start space-x-2">
                                    <svg className="h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span className="text-sm text-gray-700">{feature}</span>
                                </li>
                            ))}
                            {plan.notIncluded.map((feature) => (
                                <li key={feature} className="flex items-start space-x-2">
                                    <svg className="h-5 w-5 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span className="text-sm text-gray-400">{feature}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-8">
                            {plan.name === 'Free' ? (
                                <div className={`w-full text-center py-3 rounded-md text-sm font-medium ${plan.ctaStyle}`}>
                                    {user?.is_premium ? 'Free Plan' : plan.cta}
                                </div>
                            ) : (
                                <button
                                    onClick={() => handleUpgrade('monthly')}
                                    className={`w-full py-3 rounded-md text-sm font-medium ${plan.ctaStyle} ${user?.is_premium ? 'bg-green-100 text-green-700 cursor-default hover:bg-green-100' : ''}`}
                                    disabled={user?.is_premium || upgrading}
                                >
                                    {user?.is_premium ? 'Current Plan' : upgrading ? 'Processing...' : plan.cta}
                                </button>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            <div className="mt-12 text-center text-sm text-gray-500">
                <p>All prices in EUR. Cancel anytime. No hidden fees.</p>
            </div>
        </div>
    );
}
