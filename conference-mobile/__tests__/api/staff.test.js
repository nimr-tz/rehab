import { normalizeStaffQrToken } from '../../src/api/staff';

describe('normalizeStaffQrToken', () => {
  it('extracts the token from a public badge URL', () => {
    expect(normalizeStaffQrToken('https://summit.example.org/badge/RH26-TESTTOKEN')).toBe('RH26-TESTTOKEN');
  });

  it('extracts URL-encoded tokens and query strings safely', () => {
    expect(normalizeStaffQrToken('https://summit.example.org/badge/RH26-ABC%20123?scan=1')).toBe('RH26-ABC 123');
  });

  it('keeps plain token QR payloads unchanged', () => {
    expect(normalizeStaffQrToken(' RH26-PLAIN123 ')).toBe('RH26-PLAIN123');
  });
});
