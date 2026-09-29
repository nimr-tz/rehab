import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { getSessions } from '../api/sessions';
import { colors, radius, spacing } from '../theme';

// Session types that are not worth scanning at the door.
const NON_SCANNED_TYPES = ['break', 'lunch', 'registration'];

const formatTime = (value) => {
  if (!value) return '';
  const match = String(value).match(/T?(\d{2}):(\d{2})/);
  return match ? `${match[1]}:${match[2]}` : String(value);
};

export default function ScanModeScreen({ navigation }) {
  const { user, signOut } = useAuth();
  const [sessions, setSessions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const data = await getSessions();
      const list = Array.isArray(data) ? data : (data?.sessions || data?.data || []);
      setSessions(list.filter((s) => !NON_SCANNED_TYPES.includes(String(s.session_type || '').toLowerCase())));
    } catch (err) {
      setError(err.isNetworkError ? 'Cannot reach the server.' : 'Could not load sessions.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const grouped = useMemo(() => {
    return [...sessions].sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
  }, [sessions]);

  const header = (
    <View>
      <View style={styles.topRow}>
        <View style={styles.flex}>
          <Text style={styles.eyebrow}>Signed in</Text>
          <Text style={styles.name} numberOfLines={1}>{user?.name}</Text>
        </View>
        <TouchableOpacity onPress={signOut} style={styles.signOut}>
          <Ionicons name="log-out-outline" size={20} color={colors.primary} />
          <Text style={styles.signOutText}>Sign out</Text>
        </TouchableOpacity>
      </View>

      <TouchableOpacity
        style={styles.entranceCard}
        onPress={() => navigation.navigate('Scanner', { mode: 'entrance' })}
      >
        <Ionicons name="enter-outline" size={28} color={colors.primaryDark} />
        <View style={styles.flex}>
          <Text style={styles.entranceTitle}>Conference entrance</Text>
          <Text style={styles.entranceText}>Record daily attendance at the registration desk.</Text>
        </View>
        <Ionicons name="chevron-forward" size={22} color={colors.primaryDark} />
      </TouchableOpacity>

      <Text style={styles.sectionTitle}>Session doors</Text>
      <Text style={styles.sectionHint}>Choose the session you are scanning into. Each scan counts towards CPD credit.</Text>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );

  if (loading) {
    return (
      <SafeAreaView style={[styles.root, styles.center]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.root} edges={['top', 'left', 'right']}>
      <FlatList
        data={grouped}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={header}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={false} onRefresh={load} />}
        ListEmptyComponent={!error ? <Text style={styles.empty}>No sessions published yet.</Text> : null}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.sessionCard}
            onPress={() => navigation.navigate('Scanner', { mode: 'session', session: { id: item.id, name: item.name } })}
          >
            <View style={styles.flex}>
              <Text style={styles.sessionName} numberOfLines={2}>{item.name}</Text>
              <Text style={styles.sessionMeta} numberOfLines={1}>
                {[item.schedule_days?.[0], `${formatTime(item.start_time)}–${formatTime(item.end_time)}`, item.room_location].filter(Boolean).join(' · ')}
              </Text>
            </View>
            <Ionicons name="qr-code-outline" size={22} color={colors.primary} />
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  center: { justifyContent: 'center', alignItems: 'center' },
  flex: { flex: 1 },
  list: { padding: spacing.md, paddingBottom: spacing.xl },
  topRow: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.lg },
  eyebrow: { color: colors.textLight, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1 },
  name: { color: colors.text, fontSize: 20, fontWeight: '900' },
  signOut: { flexDirection: 'row', alignItems: 'center', gap: 4, padding: spacing.sm },
  signOutText: { color: colors.primary, fontWeight: '700' },
  entranceCard: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.md, backgroundColor: colors.accent,
    borderRadius: radius.lg, padding: spacing.md, marginBottom: spacing.lg,
  },
  entranceTitle: { color: colors.primaryDark, fontSize: 17, fontWeight: '900' },
  entranceText: { color: colors.primaryDark, fontSize: 13, marginTop: 2 },
  sectionTitle: { color: colors.text, fontSize: 16, fontWeight: '900' },
  sectionHint: { color: colors.textSecondary, fontSize: 13, marginTop: 2, marginBottom: spacing.md },
  error: { color: colors.error, fontWeight: '700', marginBottom: spacing.md },
  empty: { color: colors.textSecondary, textAlign: 'center', marginTop: spacing.lg },
  sessionCard: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.md, backgroundColor: colors.surface,
    borderRadius: radius.md, borderWidth: 1, borderColor: colors.border, padding: spacing.md, marginBottom: spacing.sm,
  },
  sessionName: { color: colors.text, fontSize: 15, fontWeight: '800' },
  sessionMeta: { color: colors.textSecondary, fontSize: 12, marginTop: 4 },
});
