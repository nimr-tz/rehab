<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    public function detectDuplicateAccounts(): Collection
    {
        $users = User::whereHas('abstractSubmissions')
            ->withCount('abstractSubmissions')
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'affiliation',
                'created_at',
            ]);

        $flags = collect();
        $seenPairs = [];

        $candidateBuckets = [];

        foreach ($users as $user) {
            $fullName = $this->normalizeText(trim($user->first_name . ' ' . $user->last_name));
            $phone = $this->normalizePhone((string) $user->phone);
            $affiliation = $this->normalizeText((string) $user->affiliation);

            if ($phone !== '') {
                $candidateBuckets['phone:' . $phone][] = $user;
            }

            if ($fullName !== '') {
                $candidateBuckets['name:' . $fullName][] = $user;
            }

            if ($fullName !== '' && $affiliation !== '') {
                $candidateBuckets['aff-name-prefix:' . $affiliation . ':' . substr($fullName, 0, 10)][] = $user;
            }
        }

        foreach ($candidateBuckets as $bucket) {
            $this->collectAccountMatchesFromBucket(collect($bucket)->unique('id')->values(), $flags, $seenPairs);
        }

        return $flags
            ->sortByDesc(fn ($item) => ($item['confidence_rank'] * 1000) + $item['score'])
            ->values();
    }

    public function detectDuplicateAbstracts(): Collection
    {
        $abstracts = AbstractSubmission::where('status', '!=', 'draft')
            ->with('user:id,first_name,last_name,email')
            ->get([
                'id',
                'user_id',
                'author_name',
                'title',
                'description',
                'subtheme',
                'status',
                'created_at',
            ]);

        $flags = collect();
        $seenPairs = [];

        $candidateBuckets = [];

        foreach ($abstracts as $abstract) {
            $title = $this->normalizeText((string) $abstract->title);
            $author = $this->normalizeText((string) $abstract->author_name);

            if ($title !== '') {
                $candidateBuckets['title:' . $title][] = $abstract;
                $candidateBuckets['title-prefix:' . substr($title, 0, 24)][] = $abstract;
            }

            if ($title !== '' && $author !== '') {
                $candidateBuckets['author-title-prefix:' . $author . ':' . substr($title, 0, 18)][] = $abstract;
            }

            if ($abstract->user_id) {
                $candidateBuckets['user:' . $abstract->user_id][] = $abstract;
            }
        }

        foreach ($candidateBuckets as $bucket) {
            $this->collectAbstractMatchesFromBucket(collect($bucket)->unique('id')->values(), $flags, $seenPairs);
        }

        return $flags
            ->sortByDesc(fn ($item) => ($item['confidence_rank'] * 1000) + $item['score'])
            ->values();
    }

    private function collectAccountMatchesFromBucket(Collection $users, Collection $flags, array &$seenPairs): void
    {
        $count = $users->count();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $users[$i];
                $right = $users[$j];
                $pairKey = $this->pairKey($left->id, $right->id);

                if (isset($seenPairs[$pairKey])) {
                    continue;
                }

                $match = $this->scoreAccountPair($left, $right);
                if (!$match) {
                    continue;
                }

                $seenPairs[$pairKey] = true;
                $flags->push([
                    'type' => 'account',
                    'confidence' => $match['confidence'],
                    'confidence_rank' => $match['confidence_rank'],
                    'score' => $match['score'],
                    'reasons' => $match['reasons'],
                    'left' => $left,
                    'right' => $right,
                ]);
            }
        }
    }

    private function collectAbstractMatchesFromBucket(Collection $abstracts, Collection $flags, array &$seenPairs): void
    {
        $count = $abstracts->count();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $abstracts[$i];
                $right = $abstracts[$j];
                $pairKey = $this->pairKey($left->id, $right->id);

                if (isset($seenPairs[$pairKey])) {
                    continue;
                }

                $match = $this->scoreAbstractPair($left, $right);
                if (!$match) {
                    continue;
                }

                $seenPairs[$pairKey] = true;
                $flags->push([
                    'type' => 'abstract',
                    'confidence' => $match['confidence'],
                    'confidence_rank' => $match['confidence_rank'],
                    'score' => $match['score'],
                    'reasons' => $match['reasons'],
                    'left' => $left,
                    'right' => $right,
                ]);
            }
        }
    }

    private function scoreAccountPair(User $left, User $right): ?array
    {
        $leftName = $this->normalizeText(trim($left->first_name . ' ' . $left->last_name));
        $rightName = $this->normalizeText(trim($right->first_name . ' ' . $right->last_name));
        $leftAffiliation = $this->normalizeText((string) $left->affiliation);
        $rightAffiliation = $this->normalizeText((string) $right->affiliation);
        $leftPhone = $this->normalizePhone((string) $left->phone);
        $rightPhone = $this->normalizePhone((string) $right->phone);

        $nameSimilarity = $this->similarityPercent($leftName, $rightName);
        $sameAffiliation = $leftAffiliation !== '' && $leftAffiliation === $rightAffiliation;
        $samePhone = $leftPhone !== '' && $leftPhone === $rightPhone;
        $reasons = [];
        $score = 0;
        $confidence = null;
        $confidenceRank = 0;

        if ($samePhone) {
            $reasons[] = 'Same phone number';
            $score += 100;
            $confidence = 'High';
            $confidenceRank = 3;
        }

        if ($leftName !== '' && $leftName === $rightName && $sameAffiliation) {
            $reasons[] = 'Exact same name and affiliation';
            $score += 95;
            $confidence = 'High';
            $confidenceRank = 3;
        } elseif ($sameAffiliation && $nameSimilarity >= 92) {
            $reasons[] = "Very similar name ({$nameSimilarity}%) with same affiliation";
            $score += 82;
            $confidence ??= 'Medium';
            $confidenceRank = max($confidenceRank, 2);
        } elseif ($leftName !== '' && $leftName === $rightName) {
            $reasons[] = 'Exact same name';
            $score += 78;
            $confidence ??= 'Medium';
            $confidenceRank = max($confidenceRank, 2);
        }

        if ($sameAffiliation && $confidenceRank > 0 && !in_array('Same affiliation', $reasons, true)) {
            $reasons[] = 'Same affiliation';
        }

        if ($confidenceRank === 0) {
            return null;
        }

        return [
            'confidence' => $confidence ?? 'Medium',
            'confidence_rank' => $confidenceRank,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function scoreAbstractPair(AbstractSubmission $left, AbstractSubmission $right): ?array
    {
        $leftTitle = $this->normalizeText($left->title);
        $rightTitle = $this->normalizeText($right->title);
        $leftAuthor = $this->normalizeText((string) $left->author_name);
        $rightAuthor = $this->normalizeText((string) $right->author_name);
        $leftDescription = $this->normalizeText((string) $left->description);
        $rightDescription = $this->normalizeText((string) $right->description);

        $titleSimilarity = $this->similarityPercent($leftTitle, $rightTitle);
        $descriptionSimilarity = $this->similarityPercent($leftDescription, $rightDescription);
        $sameAuthor = $leftAuthor !== '' && $leftAuthor === $rightAuthor;
        $sameUser = $left->user_id && $left->user_id === $right->user_id;
        $reasons = [];
        $score = 0;
        $confidence = null;
        $confidenceRank = 0;

        if ($leftTitle !== '' && $leftTitle === $rightTitle) {
            $reasons[] = 'Exact same title';
            $score += 100;
            $confidence = 'High';
            $confidenceRank = 3;
        }

        if ($sameAuthor && $titleSimilarity >= 90) {
            $reasons[] = "Same author and very similar title ({$titleSimilarity}%)";
            $score += 92;
            $confidence = 'High';
            $confidenceRank = 3;
        } elseif ($titleSimilarity >= 96) {
            $reasons[] = "Very similar title ({$titleSimilarity}%)";
            $score += 84;
            $confidence ??= 'Medium';
            $confidenceRank = max($confidenceRank, 2);
        } elseif ($titleSimilarity >= 88 && $descriptionSimilarity >= 78) {
            $reasons[] = "Similar title ({$titleSimilarity}%) and abstract body ({$descriptionSimilarity}%)";
            $score += 80;
            $confidence ??= 'Medium';
            $confidenceRank = max($confidenceRank, 2);
        }

        if ($sameUser && $confidenceRank > 0) {
            $reasons[] = 'Submitted from the same account';
        }

        if ($sameAuthor && $confidenceRank > 0 && !in_array('Same author name', $reasons, true)) {
            $reasons[] = 'Same author name';
        }

        if ($confidenceRank === 0) {
            return null;
        }

        return [
            'confidence' => $confidence ?? 'Medium',
            'confidence_rank' => $confidenceRank,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function normalizeText(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function normalizePhone(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function similarityPercent(string $left, string $right): int
    {
        if ($left === '' || $right === '') {
            return 0;
        }

        similar_text($left, $right, $percent);

        return (int) round($percent);
    }

    private function pairKey(int|string $leftId, int|string $rightId): string
    {
        $ids = [(string) $leftId, (string) $rightId];
        sort($ids);

        return implode(':', $ids);
    }
}
