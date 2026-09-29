<?php

namespace Tests\Unit;

use App\Models\AbstractSubmission;
use App\Services\SessionTopicDetectionService;
use Tests\TestCase;

class SessionTopicDetectionServiceTest extends TestCase
{
    public function test_detects_topic_from_compound_oral_conference_code(): void
    {
        $result = (new SessionTopicDetectionService)->detect(new AbstractSubmission([
            'conference_code' => 'OR-HBR-01',
            'title' => 'General research title',
        ]));

        $this->assertSame('HBR', $result['code']);
        $this->assertSame('Home-Based Rehabilitation', $result['topic']);
        $this->assertSame('code_prefix', $result['source']);
    }

    public function test_detects_topic_from_subtheme_when_no_code(): void
    {
        $result = (new SessionTopicDetectionService)->detect(new AbstractSubmission([
            'subtheme' => 'Rehabilitation and Non-Communicable Diseases (NCDs)',
            'title' => 'General research title',
        ]));

        $this->assertSame('NCD', $result['code']);
        $this->assertSame('subtheme', $result['source']);
    }

    public function test_unknown_topic_returns_null(): void
    {
        $this->assertNull((new SessionTopicDetectionService)->detect(new AbstractSubmission([
            'subtheme' => 'Malaria',
            'title' => 'General research title',
        ])));
    }
}
