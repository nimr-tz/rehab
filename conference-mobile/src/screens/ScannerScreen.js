import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator, Platform, ScrollView, StatusBar, StyleSheet,
  Text, TouchableOpacity, Vibration, View,
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  getSessionAttendanceSummary, lookupAttendeeByQR, markAttendance, recordSessionAttendance,
} from '../api/staff';
import { colors, radius, spacing } from '../theme';

// Session-door results clear themselves so the next person can be scanned.
const AUTO_RESET_MS = 2500;

const haptic = (pattern) => {
  if (Platform.OS !== 'web') Vibration.vibrate(pattern);
};

const RESULT_STYLES = {
  success: { bg: colors.successLight, fg: colors.success, icon: 'checkmark-circle' },
  warning: { bg: colors.warningLight, fg: colors.warning, icon: 'alert-circle' },
  error: { bg: colors.errorLight, fg: colors.error, icon: 'close-circle' },
};

export default function ScannerScreen({ navigation, route }) {
  const { mode, session } = route.params || {};
  const isSessionMode = mode === 'session';
  const insets = useSafeAreaInsets();
  const [permission, requestPermission] = useCameraPermissions();

  const [busy, setBusy] = useState(false);
  const [paused, setPaused] = useState(false);
  const [torch, setTorch] = useState(false);
  const [result, setResult] = useState(null);       // { tone, title, detail }
  const [attendee, setAttendee] = useState(null);   // entrance mode lookup
  const [count, setCount] = useState(null);
  const resetTimer = useRef(null);

  const reset = useCallback(() => {
    if (resetTimer.current) clearTimeout(resetTimer.current);
    setResult(null);
    setAttendee(null);
    setPaused(false);
  }, []);

  useEffect(() => () => resetTimer.current && clearTimeout(resetTimer.current), []);

  useEffect(() => {
    if (isSessionMode && session?.id) {
      getSessionAttendanceSummary(session.id).then((data) => setCount(data?.count ?? 0)).catch(() => {});
    }
  }, [isSessionMode, session?.id]);

  const showResult = (tone, title, detail, autoReset) => {
    setResult({ tone, title, detail });
    haptic(tone === 'success' ? [0, 80, 40, 80] : [0, 250]);
    if (autoReset) {
      resetTimer.current = setTimeout(reset, AUTO_RESET_MS);
    }
  };

  const handleSessionScan = async (data) => {
    try {
      const res = await recordSessionAttendance(session.id, data);
      setCount(res.session_count ?? ((count ?? 0) + 1));
      showResult('success', res.attendee?.name || 'Recorded', res.attendee?.affiliation || 'Attendance recorded', true);
    } catch (err) {
      const body = err.data || {};
      if (err.status === 409) {
        if (typeof body.session_count === 'number') setCount(body.session_count);
        showResult('warning', body.attendee?.name || 'Already recorded', body.message || 'Already recorded for this session.', true);
      } else if (err.status === 400) {
        showResult('error', body.attendee?.name || 'Not registered', body.message || 'Registration is not confirmed.', false);
      } else if (err.status === 404) {
        showResult('error', 'Badge not recognised', 'Send the attendee to the registration desk.', false);
      } else {
        showResult('error', 'Scan failed', err.isNetworkError ? 'No connection to the server.' : (body.message || 'Please try again.'), false);
      }
    }
  };

  const handleEntranceScan = async (data) => {
    try {
      const res = await lookupAttendeeByQR(data);
      if (res.success) {
        setAttendee(res.attendee);
      } else {
        showResult('error', 'Badge not recognised', res.message || 'Attendee not found.', false);
      }
    } catch (err) {
      showResult('error', 'Badge not recognised', err.data?.message || 'Attendee not found.', false);
    }
  };

  const onBarcodeScanned = async ({ data }) => {
    if (paused || busy) return;
    setPaused(true);
    setBusy(true);
    haptic(60);
    try {
      if (isSessionMode) {
        await handleSessionScan(data);
      } else {
        await handleEntranceScan(data);
      }
    } finally {
      setBusy(false);
    }
  };

  const onMarkPresent = async () => {
    if (!attendee) return;
    setBusy(true);
    try {
      const res = await markAttendance(attendee.id, attendee.current_day, attendee.attendee_type || 'user');
      setAttendee(null);
      showResult('success', attendee.name, res.message || 'Marked present.', true);
    } catch (err) {
      setAttendee(null);
      showResult('warning', attendee.name, err.data?.message || 'Could not mark attendance.', false);
    } finally {
      setBusy(false);
    }
  };

  if (!permission) {
    return <View style={[styles.root, styles.center]}><ActivityIndicator color={colors.textInverse} /></View>;
  }

  if (!permission.granted) {
    return (
      <View style={[styles.root, styles.center, { padding: spacing.lg }]}>
        <Ionicons name="camera-outline" size={56} color={colors.textInverse} />
        <Text style={styles.permissionTitle}>Camera access needed</Text>
        <Text style={styles.permissionText}>The camera is used only to read attendee badge QR codes.</Text>
        <TouchableOpacity style={styles.primaryButton} onPress={requestPermission}>
          <Text style={styles.primaryButtonText}>Allow camera</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const tone = result ? RESULT_STYLES[result.tone] : null;

  return (
    <View style={styles.root}>
      <StatusBar barStyle="light-content" />
      <CameraView
        style={StyleSheet.absoluteFill}
        facing="back"
        enableTorch={torch}
        barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
        onBarcodeScanned={paused ? undefined : onBarcodeScanned}
      />

      <View style={[styles.topBar, { paddingTop: insets.top + spacing.sm }]}>
        <TouchableOpacity style={styles.iconButton} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={22} color={colors.textInverse} />
        </TouchableOpacity>
        <View style={styles.topCenter}>
          <Text style={styles.topEyebrow}>{isSessionMode ? 'Session door' : 'Conference entrance'}</Text>
          <Text style={styles.topTitle} numberOfLines={1}>{isSessionMode ? session?.name : 'Daily attendance'}</Text>
          {isSessionMode && count !== null ? <Text style={styles.counter}>{count} recorded</Text> : null}
        </View>
        <TouchableOpacity style={styles.iconButton} onPress={() => setTorch((on) => !on)}>
          <Ionicons name={torch ? 'flash' : 'flash-outline'} size={22} color={colors.textInverse} />
        </TouchableOpacity>
      </View>

      {!paused && (
        <View style={styles.frameWrap} pointerEvents="none">
          <View style={styles.frame} />
          <Text style={styles.hint}>Point the camera at the badge QR code</Text>
        </View>
      )}

      {(busy || result || attendee) && (
        <View style={[styles.sheet, { paddingBottom: insets.bottom + spacing.md }]}>
          {busy && !attendee ? (
            <View style={styles.center}>
              <ActivityIndicator size="large" color={colors.primary} />
            </View>
          ) : attendee ? (
            <ScrollView bounces={false}>
              <Text style={styles.attendeeName}>{attendee.title ? `${attendee.title} ` : ''}{attendee.name}</Text>
              {attendee.affiliation ? <Text style={styles.attendeeMeta}>{attendee.affiliation}</Text> : null}
              {attendee.attended_today ? (
                <View style={[styles.banner, { backgroundColor: colors.successLight }]}>
                  <Ionicons name="checkmark-circle" size={22} color={colors.success} />
                  <Text style={[styles.bannerText, { color: colors.success }]}>
                    Already checked in today{attendee.today_checkin_time ? ` at ${attendee.today_checkin_time}` : ''}
                  </Text>
                </View>
              ) : attendee.is_paid ? (
                <TouchableOpacity style={styles.primaryButton} onPress={onMarkPresent} disabled={busy}>
                  {busy ? <ActivityIndicator color={colors.primaryDark} /> : (
                    <Text style={styles.primaryButtonText}>Mark present — {attendee.current_day_label || `Day ${attendee.current_day}`}</Text>
                  )}
                </TouchableOpacity>
              ) : (
                <View style={[styles.banner, { backgroundColor: colors.warningLight }]}>
                  <Ionicons name="alert-circle" size={22} color={colors.warning} />
                  <Text style={[styles.bannerText, { color: colors.warning }]}>Registration not confirmed — send to the finance desk.</Text>
                </View>
              )}
              <TouchableOpacity style={styles.secondaryButton} onPress={reset}>
                <Text style={styles.secondaryButtonText}>Scan next badge</Text>
              </TouchableOpacity>
            </ScrollView>
          ) : result ? (
            <View>
              <View style={[styles.banner, { backgroundColor: tone.bg }]}>
                <Ionicons name={tone.icon} size={28} color={tone.fg} />
                <View style={styles.flex}>
                  <Text style={[styles.resultTitle, { color: tone.fg }]}>{result.title}</Text>
                  <Text style={[styles.bannerText, { color: tone.fg }]}>{result.detail}</Text>
                </View>
              </View>
              <TouchableOpacity style={styles.secondaryButton} onPress={reset}>
                <Text style={styles.secondaryButtonText}>Scan next badge</Text>
              </TouchableOpacity>
            </View>
          ) : null}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#000' },
  center: { alignItems: 'center', justifyContent: 'center', paddingVertical: spacing.lg },
  flex: { flex: 1 },
  topBar: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingHorizontal: spacing.md,
    paddingBottom: spacing.md, backgroundColor: 'rgba(14,53,69,0.85)',
  },
  iconButton: {
    width: 40, height: 40, borderRadius: radius.md, alignItems: 'center', justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.12)',
  },
  topCenter: { flex: 1, alignItems: 'center' },
  topEyebrow: { color: colors.accent, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1 },
  topTitle: { color: colors.textInverse, fontSize: 16, fontWeight: '900' },
  counter: { color: '#A7F3D0', fontSize: 12, fontWeight: '800', marginTop: 2 },
  frameWrap: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  frame: { width: 250, height: 250, borderRadius: radius.lg, borderWidth: 4, borderColor: colors.accent },
  hint: { color: 'rgba(255,255,255,0.85)', marginTop: spacing.md, fontSize: 14, fontWeight: '600' },
  sheet: {
    position: 'absolute', left: 0, right: 0, bottom: 0, backgroundColor: colors.surface,
    borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: spacing.md, maxHeight: '70%',
  },
  attendeeName: { color: colors.text, fontSize: 20, fontWeight: '900' },
  attendeeMeta: { color: colors.textSecondary, fontSize: 14, marginTop: 2, marginBottom: spacing.md },
  banner: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.sm, borderRadius: radius.md,
    padding: spacing.md, marginBottom: spacing.sm,
  },
  bannerText: { fontSize: 14, fontWeight: '700', flexShrink: 1 },
  resultTitle: { fontSize: 18, fontWeight: '900' },
  primaryButton: {
    backgroundColor: colors.accent, borderRadius: radius.md, paddingVertical: 16,
    alignItems: 'center', marginBottom: spacing.sm, marginTop: spacing.sm,
  },
  primaryButtonText: { color: colors.primaryDark, fontSize: 16, fontWeight: '900' },
  secondaryButton: {
    borderRadius: radius.md, paddingVertical: 14, alignItems: 'center',
    backgroundColor: colors.primaryLight,
  },
  secondaryButtonText: { color: colors.primary, fontSize: 15, fontWeight: '800' },
  permissionTitle: { color: colors.textInverse, fontSize: 20, fontWeight: '900', marginTop: spacing.md },
  permissionText: { color: 'rgba(255,255,255,0.8)', textAlign: 'center', marginTop: spacing.sm, marginBottom: spacing.lg },
});
