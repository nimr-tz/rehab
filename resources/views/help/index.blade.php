@extends('layouts.app')

@section('title', 'Help Documentation')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Help Documentation</h1>
                    <p class="text-gray-600">Welcome to the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Management System help documentation. Find answers to common questions and learn how to use the system effectively.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Getting Started -->
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-blue-900">Getting Started</h2>
                        </div>
                        <p class="text-blue-700 mb-4">New to the system? Learn the basics of submitting abstracts and managing your account.</p>
                        <a href="{{ route('help.article', 'getting-started') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                            Learn More
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Abstract Submission -->
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-green-900">Abstract Submission</h2>
                        </div>
                        <p class="text-green-700 mb-4">Step-by-step guide to submitting your abstract and managing your submissions.</p>
                        <a href="{{ route('help.article', 'abstract-submission') }}" class="inline-flex items-center text-green-600 hover:text-green-800 font-medium">
                            Learn More
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Review Process -->
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-purple-900">Review Process</h2>
                        </div>
                        <p class="text-purple-700 mb-4">Understanding how the peer review process works and what to expect.</p>
                        <a href="{{ route('help.article', 'review-process') }}" class="inline-flex items-center text-purple-600 hover:text-purple-800 font-medium">
                            Learn More
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Admin Guide -->
                    <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-orange-900">Admin Guide</h2>
                        </div>
                        <p class="text-orange-700 mb-4">Administrative functions and system management for conference organizers.</p>
                        <a href="{{ route('help.article', 'admin-guide') }}" class="inline-flex items-center text-orange-600 hover:text-orange-800 font-medium">
                            Learn More
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- FAQ -->
                    <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-yellow-900">Frequently Asked Questions</h2>
                        </div>
                        <p class="text-yellow-700 mb-4">Quick answers to the most common questions about the system.</p>
                        <a href="{{ route('help.faq') }}" class="inline-flex items-center text-yellow-600 hover:text-yellow-800 font-medium">
                            View FAQ
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-red-900">Contact Support</h2>
                        </div>
                        <p class="text-red-700 mb-4">Need additional help? Contact our support team for assistance.</p>
                        <a href="{{ route('help.contact') }}" class="inline-flex items-center text-red-600 hover:text-red-800 font-medium">
                            Contact Us
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Quick Search</h3>
                    <form action="{{ route('help.search') }}" method="GET" class="flex gap-2">
                        <input type="text" name="q" placeholder="Search help articles..."
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
