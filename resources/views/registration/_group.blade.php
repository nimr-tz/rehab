{{-- Someone registered by a group: the leader pays for everyone at once. --}}
@php $group = $registration->group; @endphp

<x-card>
    <x-empty icon="users" :title="'Paid for by '.$group->name">
        {{ $group->leader->name }} registered you with {{ $group->name }} ({{ $group->reference }}) and pays the fee for the whole group.
        @if ($registration->status === \App\Enums\RegistrationStatus::PaymentSubmitted)
            Their payment is being verified; you will be emailed when your place is confirmed.
        @else
            You will be emailed as soon as the group's payment is verified, and can then download your badge.
        @endif
        Questions about the payment? Contact {{ $group->leader->first_name }} at {{ $group->leader->email }}.
    </x-empty>
</x-card>
