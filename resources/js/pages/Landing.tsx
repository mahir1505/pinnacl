import { Link } from 'react-router-dom';

const features = [
    {
        title: 'Algorithmic Scoring',
        description: 'Get a comprehensive 0-100 score across 6 categories including engagement, consistency, and growth.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
        ),
    },
    {
        title: 'Multi-Platform',
        description: 'Connect Instagram, TikTok, YouTube, X, and LinkedIn. All your profiles in one place.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
            </svg>
        ),
    },
    {
        title: 'Actionable Tips',
        description: 'Get specific, data-driven recommendations to improve your engagement, growth, and visibility.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
        ),
    },
    {
        title: 'Growth Analytics',
        description: 'Track followers, engagement, and performance over time with interactive charts and trends.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
            </svg>
        ),
    },
    {
        title: 'Shareable Score Cards',
        description: 'Share your Pinnacl score with a beautiful, branded card. Perfect for your bio or stories.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
            </svg>
        ),
    },
    {
        title: 'Post-Level Tracking',
        description: 'Premium: Track every post, reel, and video with detailed metrics and performance insights.',
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
        ),
    },
];

export default function Landing() {
    return (
        <div className="min-h-screen bg-white">
            {/* Nav */}
            <nav className="border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16 items-center">
                        <span className="text-2xl font-bold text-indigo-600">Pinnacl</span>
                        <div className="flex items-center space-x-4">
                            <Link to="/login" className="text-sm font-medium text-gray-500 hover:text-gray-700">
                                Log in
                            </Link>
                            <Link to="/register" className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Get Started — Free
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Hero */}
            <section className="pt-20 pb-16">
                <div className="max-w-4xl mx-auto text-center px-4">
                    <h1 className="text-5xl font-bold text-gray-900 leading-tight">
                        Know Your Social Media Score.
                        <br />
                        <span className="text-indigo-600">Grow Smarter.</span>
                    </h1>
                    <p className="mt-6 text-xl text-gray-500 max-w-2xl mx-auto">
                        Pinnacl analyzes your social media profiles across 6 key categories and gives you a clear score
                        with actionable tips to improve your reach, engagement, and growth.
                    </p>
                    <div className="mt-10 flex justify-center space-x-4">
                        <Link
                            to="/register"
                            className="px-8 py-3 text-lg font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-lg shadow-indigo-200"
                        >
                            Check Your Score — Free
                        </Link>
                    </div>
                    <p className="mt-4 text-sm text-gray-400">No credit card required. Free forever for 1 platform.</p>
                </div>
            </section>

            {/* Score preview */}
            <section className="py-16 bg-gray-50">
                <div className="max-w-5xl mx-auto px-4">
                    <div className="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl shadow-2xl p-8 md:p-12 text-white">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                            <div>
                                <p className="text-5xl font-bold">82</p>
                                <p className="mt-2 text-indigo-200">Average Pinnacl Score</p>
                            </div>
                            <div>
                                <p className="text-5xl font-bold">6</p>
                                <p className="mt-2 text-indigo-200">Score Categories</p>
                            </div>
                            <div>
                                <p className="text-5xl font-bold">5</p>
                                <p className="mt-2 text-indigo-200">Platforms Supported</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Features */}
            <section className="py-20">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="text-center mb-16">
                        <h2 className="text-3xl font-bold text-gray-900">Everything you need to grow</h2>
                        <p className="mt-4 text-lg text-gray-500">
                            Comprehensive analytics and insights for content creators.
                        </p>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        {features.map((feature) => (
                            <div key={feature.title} className="p-6 rounded-lg border border-gray-200 hover:border-indigo-200 hover:shadow-md transition-all">
                                <div className="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">{feature.title}</h3>
                                <p className="mt-2 text-sm text-gray-500">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Score categories */}
            <section className="py-20 bg-gray-50">
                <div className="max-w-4xl mx-auto px-4">
                    <div className="text-center mb-12">
                        <h2 className="text-3xl font-bold text-gray-900">How We Score Your Profile</h2>
                        <p className="mt-4 text-lg text-gray-500">
                            A weighted algorithm across 6 categories for a complete picture.
                        </p>
                    </div>
                    <div className="space-y-4">
                        {[
                            { name: 'Engagement Rate', weight: 25, color: 'bg-green-500' },
                            { name: 'Post Consistency', weight: 20, color: 'bg-yellow-500' },
                            { name: 'Content Performance', weight: 20, color: 'bg-purple-500' },
                            { name: 'Profile Completeness', weight: 15, color: 'bg-blue-500' },
                            { name: 'Growth Trend', weight: 10, color: 'bg-pink-500' },
                            { name: 'Hashtag & SEO', weight: 10, color: 'bg-cyan-500' },
                        ].map((cat) => (
                            <div key={cat.name} className="flex items-center space-x-4">
                                <span className="w-44 text-sm font-medium text-gray-700 text-right">{cat.name}</span>
                                <div className="flex-1 bg-gray-200 rounded-full h-4">
                                    <div className={`${cat.color} h-4 rounded-full`} style={{ width: `${cat.weight * 4}%` }}></div>
                                </div>
                                <span className="w-12 text-sm font-bold text-gray-700">{cat.weight}%</span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="py-20">
                <div className="max-w-4xl mx-auto text-center px-4">
                    <h2 className="text-3xl font-bold text-gray-900">Ready to level up your social media?</h2>
                    <p className="mt-4 text-lg text-gray-500">
                        Join Pinnacl today and get your first score in minutes.
                    </p>
                    <div className="mt-8">
                        <Link
                            to="/register"
                            className="px-8 py-3 text-lg font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-lg shadow-indigo-200"
                        >
                            Get Started — Free
                        </Link>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-gray-200 py-12">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <span className="text-lg font-bold text-indigo-600">Pinnacl</span>
                    <p className="mt-2 text-sm text-gray-500">
                        The social media profile scoring tool for content creators.
                    </p>
                </div>
            </footer>
        </div>
    );
}
