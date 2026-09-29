@extends('layouts.app')

@section('title', 'Frequently Asked Questions')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h1>
                    <p class="text-gray-600">Find quick answers to the most common questions about the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Management System.</p>
                </div>

                <div class="space-y-4" x-data="{ openItem: null }">
                    <!-- General Questions -->
                    <div class="border border-gray-200 rounded-lg">
                        <div class="bg-gray-50 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">General Questions</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q1' ? null : 'q1'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">How do I create an account?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q1' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q1'" x-transition class="mt-4 text-gray-600">
                                    <p>To create an account, click the "Register" link in the top navigation. Fill in your details including name, email, and password. You'll receive a confirmation email to verify your account.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q2' ? null : 'q2'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">How do I submit an abstract?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q2' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q2'" x-transition class="mt-4 text-gray-600">
                                    <p>After logging in, click "Submit Abstract" in your dashboard. Fill in the required fields including title, abstract text, keywords, and select appropriate categories. You can save as draft or submit immediately.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q3' ? null : 'q3'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">Can I edit my abstract after submission?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q3' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q3'" x-transition class="mt-4 text-gray-600">
                                    <p>Yes, you can edit your abstract until the submission deadline. Go to "My Submissions" in your dashboard and click "Edit" on the abstract you want to modify.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review Process -->
                    <div class="border border-gray-200 rounded-lg">
                        <div class="bg-gray-50 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">Review Process</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q4' ? null : 'q4'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">How does the review process work?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q4' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q4'" x-transition class="mt-4 text-gray-600">
                                    <p>Each abstract is reviewed by multiple experts in the field. Reviewers evaluate the quality, relevance, and scientific merit of your work. You'll receive feedback and a decision within the specified timeline.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q5' ? null : 'q5'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">When will I receive my review results?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q5' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q5'" x-transition class="mt-4 text-gray-600">
                                    <p>Review results are typically available within 4-6 weeks after the submission deadline. You'll receive an email notification when your results are ready, and you can view them in your dashboard.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q6' ? null : 'q6'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">What are the possible review outcomes?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q6' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q6'" x-transition class="mt-4 text-gray-600">
                                    <p>Possible outcomes include: Accepted, Accepted with Minor Revisions, Major Revisions Required, or Rejected. Each outcome comes with detailed feedback from the reviewers.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technical Issues -->
                    <div class="border border-gray-200 rounded-lg">
                        <div class="bg-gray-50 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">Technical Issues</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q7' ? null : 'q7'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">I forgot my password. How do I reset it?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q7' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q7'" x-transition class="mt-4 text-gray-600">
                                    <p>Click "Forgot Password" on the login page. Enter your email address and you'll receive a password reset link. Follow the link to create a new password.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q8' ? null : 'q8'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">The system is not working properly. What should I do?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q8' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q8'" x-transition class="mt-4 text-gray-600">
                                    <p>Try refreshing your browser or clearing your cache. If the problem persists, contact our support team with details about the issue you're experiencing.</p>
                                </div>
                            </div>

                            <div class="px-6 py-4">
                                <button @click="openItem = openItem === 'q9' ? null : 'q9'" 
                                        class="flex justify-between items-center w-full text-left">
                                    <span class="font-medium text-gray-900">What file formats are supported for uploads?</span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                         :class="{ 'rotate-180': openItem === 'q9' }" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="openItem === 'q9'" x-transition class="mt-4 text-gray-600">
                                    <p>Supported formats include PDF, DOC, DOCX, and TXT files. Maximum file size is 10MB. For presentations, we also accept PPT and PPTX formats.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="mt-8 p-6 bg-blue-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Still need help?</h3>
                    <p class="text-blue-700 mb-4">If you couldn't find the answer to your question, our support team is here to help.</p>
                    <a href="{{ route('help.contact') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
