# Staff app API

The staff app scans participants' badges at the summit: at each programme session for CPD attendance, and at the registration desk to look someone up. This document is the contract between the app and the portal.

- Base URL: `https://rehabhealth.apps.nimr.or.tz/api/v1` (locally `http://127.0.0.1:8100/api/v1`)
- Every request sends `Accept: application/json`. Bodies are JSON.
- Times are ISO 8601 with the server's offset, e.g. `2027-09-15T09:20:00+03:00`.

## Who can use it

Registration officers and admins sign in with their portal email and password. Any other account is refused. The role is checked on every call, so removing someone's role locks them out at once, even with a valid token.

## Signing in

```http
POST /login
{ "email": "desk@rehab.test", "password": "…", "device_name": "Hall A phone" }
```

```json
{ "token": "12|9xQ…", "user": { "id": 4, "name": "Baraka Lyimo", "email": "desk@rehab.test" } }
```

Send the token on every other call as `Authorization: Bearer 12|9xQ…`. Tokens last 14 days. A wrong password or a non-staff account returns `422` with a message under `errors.email`. Login is limited to 10 attempts a minute.

`device_name` identifies the phone in the attendance audit trail, so name it after where it is used ("Hall A phone", "Desk 2 tablet").

| Call | What it does |
|---|---|
| `GET /me` | The signed-in user, and the summit's title, dates and venue |
| `POST /logout` | Revokes this device's token only |

## What the badge holds

The QR code on a badge contains the participant's check-in code: a random string of 32 letters and digits, with nothing else. The badge number printed under it (e.g. `RH27-000123`) also works wherever a code is asked for, so staff can type it if a QR code is damaged.

## Sessions

```http
GET /sessions                 every session of the summit
GET /sessions?date=2027-09-15 one day
```

```json
{
  "data": [
    {
      "id": 7,
      "title": "Opening plenary",
      "kind": "plenary",
      "hall": "Main hall",
      "starts_at": "2027-09-15T09:00:00+03:00",
      "ends_at": "2027-09-15T10:30:00+03:00",
      "cpd_points": 1.5,
      "scannable": true,
      "open_now": true,
      "attendance_count": 182
    }
  ]
}
```

`kind` is one of `plenary`, `parallel`, `posters`, `panel` or `break`. Breaks have `scannable: false` and earn no points. `open_now` is true while the session runs: that is when scans count.

## Scanning a badge at a session

```http
POST /sessions/7/scans
{ "code": "<the QR contents or the badge number>" }
```

The response is `200` with an `outcome` for everything except an unknown badge (`404`) and invalid input (`422`). `counted` tells the app whether to show success.

```json
{
  "outcome": "recorded",
  "counted": true,
  "message": "Dr Neema Mushi recorded.",
  "attendee": { "name": "Dr Neema Mushi", "badge_number": "RH27-000123", "role": "Presenter", "payment": "paid", "…": "…" },
  "scanned_at": "2027-09-15T09:20:00+03:00",
  "session": { "id": 7, "attendance_count": 183, "…": "…" }
}
```

| `outcome` | `counted` | Meaning | What staff should do |
|---|---|---|---|
| `recorded` | true | Attendance recorded now | Let them in |
| `already_recorded` | true | Already scanned at this session | Let them in; nothing changes |
| `not_paid` | false | Registered but not paid or waived | Send them to the registration desk |
| `session_closed` | false | Scanned before the session starts or after it ends | Scan again while the session runs |
| `not_scannable` | false | The session is a break | Pick the right session |
| `not_found` | false | Not a badge for this summit (`404`) | Check the badge; send them to the desk |

Each person is recorded once per session, however often they are scanned. Their first session scan also checks them in to the summit if the desk has not.

## Who attended a session

```http
GET /sessions/7/attendance
```

Returns the session and `data`: the name, badge number and scan time of each attendee, newest first.

## Looking up a badge

```http
GET /attendees/{code}
```

```json
{
  "attendee": {
    "name": "Dr Neema Mushi",
    "badge_number": "RH27-000123",
    "role": "Presenter",
    "category": "Professional (Tanzania)",
    "institution": "Muhimbili National Hospital",
    "profession": "Physiotherapist",
    "country": "Tanzania",
    "payment": "paid",
    "confirmed": true,
    "checked_in_at": "2027-09-15T08:41:00+03:00",
    "dietary_needs": "Vegetarian",
    "accessibility_needs": null
  },
  "cpd": { "sessions_attended": 4, "points": 5.5, "points_available": 19.5 }
}
```

`payment` is `paid`, `waived`, `awaiting_mpesa` (an M-Pesa prompt is waiting for their PIN), `with_finance` (a bank or mobile money proof is being verified) or `not_paid`. Only a `confirmed` participant gets a badge or attendance.

## Errors

| Status | When |
|---|---|
| `401` | No token, or an expired or revoked one: sign in again |
| `403` | The account is not registration staff |
| `404` | Unknown badge or session |
| `422` | Invalid input; `errors` holds a message per field |
| `429` | Too many requests (600 a minute per user) |
