@extends('layouts.app')

@section('title', $article['title'] ?? 'Help Article')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Breadcrumb -->
                <nav class="flex mb-6" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('help.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                                Help
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">{{ $article['title'] ?? 'Article' }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Article Content -->
                <article class="prose prose-lg max-w-none">
                    <header class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $article['title'] ?? 'Help Article' }}</h1>
                        @if(isset($article['category']))
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $article['category'] }}
                            </span>
                        @endif
                    </header>

                    <div class="text-gray-700 leading-relaxed">
                        @if(isset($article['content']))
                            {!! $article['content'] !!}
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Article not found</h3>
                                <p class="mt-1 text-sm text-gray-500">The requested help article could not be found.</p>
                                <div class="mt-6">
                                    <a href="{{ route('help.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Back to Help
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(isset($article['related_articles']) && count($article['related_articles']) > 0)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Articles</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach($article['related_articles'] as $related)
                                    <a href="{{ route('help.article', $related['slug']) }}" class="block p-4 border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm transition-colors">
                                        <h4 class="font-medium text-gray-900 mb-2">{{ $related['title'] }}</h4>
                                        <p class="text-sm text-gray-600">{{ $related['excerpt'] ?? '' }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>

                <!-- Navigation -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <a href="{{ route('help.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to Help
                        </a>
                        <div class="flex space-x-4">
                            <a href="{{ route('help.faq') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">FAQ</a>
                            <a href="{{ route('help.contact') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
