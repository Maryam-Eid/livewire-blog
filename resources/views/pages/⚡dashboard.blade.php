<?php

use Livewire\Component;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\PostView;
use Illuminate\Support\Facades\DB;

new class extends Component {

    public function with(): array
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('editor');

        $postsQuery = $isAdmin ? Post::query()
            : Post::where('user_id', $user->id);

        // calculate stats
        $stats = [
            'total_posts' => (clone $postsQuery)->count(),
            'published_posts' => (clone $postsQuery)->published()->count(),
            'draft_posts' => (clone $postsQuery)->where('status', 'draft')->count(),
            'total_views' => (clone $postsQuery)->sum('views_count'),
            'total_comments' => $isAdmin ? Comment::count() : Comment::whereHas('post', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->count(),
            'total_users' => $isAdmin ? User::count() : null,
        ];

        // Most viewed posts
        $mostViewedPosts = (clone $postsQuery)
            ->published()
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();

        // recent comments
        $recentComments = Comment::with(['user', 'post'])
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('post', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->latest()
            ->take(5)
            ->get();

        // Views over last 7 days
        $RawViewsData = PostView::select(
            DB::raw('DATE(viewed_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('post', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->where('viewed_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $viewsData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $dateLabel = $date->format('M d');

            $viewsData->push([
                'date' => $dateLabel,
                'count' => isset($RawViewsData[$dateKey]) ? $RawViewsData[$dateKey]->count : 0
            ]);
        }

        return compact('stats', 'mostViewedPosts', 'recentComments', 'viewsData', 'isAdmin');
    }
};
?>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endassets

<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
        <a href="{{ route('blog.index') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Visit Blog
        </a>
    </div>

    <!-- Stats Grid -->
    @island
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Posts-->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Posts</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_posts'] }}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 font-medium">{{ $stats['published_posts'] }} published</span>
                <span class="text-gray-400 mx-2">•</span>
                <span class="text-yellow-600 font-medium">{{ $stats['draft_posts'] }} drafts</span>
            </div>
        </div>
        <!-- Total Views -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Views</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_views']) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600">Across all posts</p>
        </div>
        <!-- Total Comments -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Comments</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_comments']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600">Engagement from readers</p>
        </div>
        <!-- Total Users -->
        @if($isAdmin)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_users']) }}</p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-600">Registered authors & readers</p>
            </div>
        @endif
    </div>
    @endisland

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Views Chart -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Views Last 7 Days</h2>
            <div class="h-64" wire:ignore>
                <canvas id="viewsChart"></canvas>
            </div>
        </div>
        <!-- Most Viewed Posts -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Most Viewed Posts</h2>
            <div class="space-y-4">
                @forelse($mostViewedPosts as $post)
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('posts.edit', $post) }}"
                               class="text-sm font-medium text-gray-900 hover:text-indigo-600 truncate block">
                                {{ $post->title }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">{{ $post->published_at?->format('M d, Y') }}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ number_format($post->views_count) }} views
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No published posts yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <!-- Recent Comments -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Comments</h2>
        <div class="space-y-4">
            @forelse($recentComments as $comment)
                <div class="flex items-start space-x-3 pb-4 border-b border-gray-200 last:border-0 last:pb-0">
                    <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=4f46e5&color=fff"
                        alt="{{ $comment->user->name }}"
                        class="w-10 h-10 rounded-full"
                    >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $comment->user->name }}
                            <span class="text-gray-500 font-normal">commented on</span>
                            <a href="{{ route('posts.edit', $comment->post) }}"
                               class="text-indigo-600 hover:text-indigo-800">
                                {{ $comment->post->title }}
                            </a>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($comment->content, 200) }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $comment->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No comments yet.</p>
            @endforelse
        </div>
    </div>

    <div
        id="viewsChartData"
        data-labels='@json($viewsData->pluck('date')->toArray())'
        data-counts='@json($viewsData->pluck('count')->toArray())'
        hidden
    ></div>

    @script
    <script>
        const renderViewsChart = () => {
            const ctx = document.getElementById('viewsChart');
            const chartDataEl = document.getElementById('viewsChartData');

            if (!ctx || !chartDataEl || !window.Chart) {
                return;
            }

            if (window.viewsChartInstance) {
                window.viewsChartInstance.destroy();
            }

            const existingChart = window.Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            const labels = JSON.parse(chartDataEl.dataset.labels);
            const data = JSON.parse(chartDataEl.dataset.counts);
            const isDark = document.documentElement.classList.contains('dark');
            const chartTextColor = isDark ? 'rgb(212, 212, 216)' : 'rgb(82, 82, 91)';
            const chartGridColor = isDark ? 'rgba(113, 113, 122, 0.25)' : 'rgba(212, 212, 216, 0.6)';

            window.viewsChartInstance = new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Views',
                        data: data,
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(99, 102, 241)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTextColor
                            },
                            grid: {
                                color: chartGridColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: chartTextColor,
                                precision: 0
                            },
                            grid: {
                                color: chartGridColor
                            }
                        }
                    }
                }
            });
        };

        renderViewsChart();
    </script>
    @endscript
</div>
