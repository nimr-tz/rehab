<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Show help documentation index
     */
    public function index(): View
    {
        $categories = [
            'getting-started' => [
                'title' => 'Getting Started',
                'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'articles' => [
                    'account-setup' => 'Setting Up Your Account',
                    'first-submission' => 'Making Your First Submission',
                    'dashboard-overview' => 'Understanding Your Dashboard'
                ]
            ],
            'submissions' => [
                'title' => 'Abstract Submissions',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'articles' => [
                    'submission-guidelines' => 'Submission Guidelines',
                    'abstract-format' => 'Abstract Format Requirements',
                    'submission-process' => 'Submission Process',
                    'editing-submissions' => 'Editing Your Submissions',
                    'withdrawing-submissions' => 'Withdrawing Submissions'
                ]
            ],
            'reviewing' => [
                'title' => 'Review Process',
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'articles' => [
                    'review-guidelines' => 'Review Guidelines',
                    'scoring-criteria' => 'Scoring Criteria',
                    'review-process' => 'Review Process Overview',
                    'quality-standards' => 'Quality Standards'
                ]
            ],
            'admin' => [
                'title' => 'Administration',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'articles' => [
                    'user-management' => 'User Management',
                    'reviewer-assignment' => 'Reviewer Assignment',
                    'quality-management' => 'Quality Management',
                    'analytics-overview' => 'Analytics Overview'
                ]
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'articles' => [
                    'general-faq' => 'General Questions',
                    'technical-faq' => 'Technical Issues',
                    'submission-faq' => 'Submission Questions',
                    'review-faq' => 'Review Process Questions'
                ]
            ]
        ];

        return view('help.index', compact('categories'));
    }

    /**
     * Show specific help article
     */
    public function show(string $category, string $article): View
    {
        $content = $this->getArticleContent($category, $article);
        
        if (!$content) {
            abort(404, 'Help article not found');
        }

        return view('help.article', compact('content', 'category', 'article'));
    }

    /**
     * Show FAQ page
     */
    public function faq(): View
    {
        $faqs = [
            'general' => [
                'title' => 'General Questions',
                'questions' => [
                    [
                        'question' => 'How do I create an account?',
                        'answer' => 'Click on the "Register" link in the top navigation. Fill in your details and verify your email address to complete the registration process.'
                    ],
                    [
                        'question' => 'Can I submit multiple abstracts?',
                        'answer' => 'Yes, you can submit multiple abstracts. Each abstract will be reviewed independently.'
                    ],
                    [
                        'question' => 'How long does the review process take?',
                        'answer' => 'The review process typically takes 2-4 weeks. You will be notified via email when your abstract status changes.'
                    ]
                ]
            ],
            'submission' => [
                'title' => 'Submission Questions',
                'questions' => [
                    [
                        'question' => 'What format should my abstract be in?',
                        'answer' => 'Abstracts should be submitted in plain text format, with a maximum of 300 words. Include title, authors, and abstract text.'
                    ],
                    [
                        'question' => 'Can I edit my submission after submitting?',
                        'answer' => 'Yes, you can edit your submission until the review process begins. After that, you may need to contact the administrators for changes.'
                    ],
                    [
                        'question' => 'What happens if my abstract is rejected?',
                        'answer' => 'If your abstract is rejected, you will receive feedback from the reviewers. You may be able to revise and resubmit depending on the conference guidelines.'
                    ]
                ]
            ],
            'technical' => [
                'title' => 'Technical Issues',
                'questions' => [
                    [
                        'question' => 'I forgot my password. How do I reset it?',
                        'answer' => 'Click on the "Forgot Password" link on the login page. Enter your email address and follow the instructions sent to your email.'
                    ],
                    [
                        'question' => 'The system is not loading properly. What should I do?',
                        'answer' => 'Try refreshing the page or clearing your browser cache. If the problem persists, contact technical support.'
                    ],
                    [
                        'question' => 'How do I upload presentation files?',
                        'answer' => 'Once your abstract is accepted, you can upload presentation files through the presentation management section of your dashboard.'
                    ]
                ]
            ]
        ];

        return view('help.faq', compact('faqs'));
    }

    /**
     * Show contact support page
     */
    public function contact(): View
    {
        return view('help.contact');
    }

    /**
     * Get article content based on category and article
     */
    private function getArticleContent(string $category, string $article): ?array
    {
        $articles = [
            'getting-started' => [
                'account-setup' => [
                    'title' => 'Setting Up Your Account',
                    'content' => [
                        'Creating an account is the first step to participating in the conference.',
                        'Follow these steps to set up your account:',
                        '1. Click the "Register" button in the top navigation',
                        '2. Fill in your personal information including name, email, and affiliation',
                        '3. Choose a strong password',
                        '4. Verify your email address by clicking the link sent to your inbox',
                        '5. Complete your profile by adding additional information like specialization and institution',
                        'Once your account is set up, you can start submitting abstracts and participating in the review process.'
                    ]
                ],
                'first-submission' => [
                    'title' => 'Making Your First Submission',
                    'content' => [
                        'Submitting your first abstract is straightforward with our system.',
                        'Here\'s how to make your first submission:',
                        '1. Log in to your account',
                        '2. Navigate to "Submit Abstract" from your dashboard',
                        '3. Fill in the abstract details including title, authors, and abstract text',
                        '4. Select the appropriate category and subtheme',
                        '5. Review your submission carefully',
                        '6. Click "Submit" to finalize your submission',
                        'You will receive a confirmation email and can track your submission status from your dashboard.'
                    ]
                ],
                'dashboard-overview' => [
                    'title' => 'Understanding Your Dashboard',
                    'content' => [
                        'Your dashboard provides an overview of your conference participation.',
                        'Key dashboard features include:',
                        '- Abstract submissions and their current status',
                        '- Review assignments (if you are a reviewer)',
                        '- Notifications and updates',
                        '- Quick access to submission and review tools',
                        '- Profile management options',
                        'The dashboard updates in real-time to show the latest status of your submissions and any new notifications.'
                    ]
                ]
            ],
            'submissions' => [
                'submission-guidelines' => [
                    'title' => 'Submission Guidelines',
                    'content' => [
                        'Follow these guidelines to ensure your abstract is properly submitted:',
                        '1. Abstract length: Maximum 300 words',
                        '2. Format: Plain text only (no formatting or special characters)',
                        '3. Language: English only',
                        '4. Required fields: Title, authors, abstract text, category',
                        '5. Optional fields: Keywords, funding information',
                        '6. File attachments: Not allowed in initial submission',
                        '7. Multiple submissions: Allowed (each abstract reviewed independently)',
                        'Ensure all information is accurate and complete before submitting.'
                    ]
                ],
                'abstract-format' => [
                    'title' => 'Abstract Format Requirements',
                    'content' => [
                        'Your abstract must follow these format requirements:',
                        'Title:',
                        '- Maximum 200 characters',
                        '- Clear and descriptive',
                        '- No abbreviations unless standard',
                        'Authors:',
                        '- List all authors in order of contribution',
                        '- Include affiliations for each author',
                        'Abstract Text:',
                        '- Maximum 300 words',
                        '- Structured format recommended (Background, Methods, Results, Conclusion)',
                        '- No tables, figures, or references',
                        'Category:',
                        '- Select the most appropriate category from the provided list',
                        '- This helps with proper assignment to reviewers and sessions'
                    ]
                ]
            ]
        ];

        return $articles[$category][$article] ?? null;
    }
} 