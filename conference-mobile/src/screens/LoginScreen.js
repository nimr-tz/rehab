import React, { useState } from 'react';
import {
  ActivityIndicator, KeyboardAvoidingView, Platform, StyleSheet,
  Text, TextInput, TouchableOpacity, View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import { colors, radius, spacing } from '../theme';
import { APP_NAME } from '../config/appConfig';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const onSubmit = async () => {
    if (!email.trim() || !password) {
      setError('Enter your email and password.');
      return;
    }

    setSubmitting(true);
    setError(null);
    try {
      await signIn(email, password);
    } catch (err) {
      if (err.code === 'NOT_STAFF') {
        setError(err.message);
      } else if (err.isNetworkError) {
        setError('Cannot reach the server. Check your connection and try again.');
      } else {
        setError(err.data?.errors?.email?.[0] || err.data?.message || 'Sign in failed.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.root}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.flex}>
        <View style={styles.container}>
          <Text style={styles.eyebrow}>Rehabilitation Summit</Text>
          <Text style={styles.title}>{APP_NAME}</Text>
          <Text style={styles.subtitle}>Sign in with your registration staff account to scan attendee badges.</Text>

          <TextInput
            style={styles.input}
            placeholder="Email"
            placeholderTextColor={colors.textLight}
            autoCapitalize="none"
            autoComplete="email"
            keyboardType="email-address"
            value={email}
            onChangeText={setEmail}
            editable={!submitting}
          />
          <TextInput
            style={styles.input}
            placeholder="Password"
            placeholderTextColor={colors.textLight}
            secureTextEntry
            autoComplete="password"
            value={password}
            onChangeText={setPassword}
            onSubmitEditing={onSubmit}
            editable={!submitting}
          />

          {error ? <Text style={styles.error}>{error}</Text> : null}

          <TouchableOpacity style={[styles.button, submitting && styles.buttonDisabled]} onPress={onSubmit} disabled={submitting}>
            {submitting ? <ActivityIndicator color={colors.textInverse} /> : <Text style={styles.buttonText}>Sign in</Text>}
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.primary },
  flex: { flex: 1 },
  container: { flex: 1, justifyContent: 'center', padding: spacing.lg },
  eyebrow: { color: colors.accent, fontSize: 13, fontWeight: '800', letterSpacing: 1.5, textTransform: 'uppercase' },
  title: { color: colors.textInverse, fontSize: 30, fontWeight: '900', marginTop: spacing.xs },
  subtitle: { color: 'rgba(255,255,255,0.75)', fontSize: 15, marginTop: spacing.sm, marginBottom: spacing.xl, lineHeight: 21 },
  input: {
    backgroundColor: colors.surface, borderRadius: radius.md, paddingHorizontal: spacing.md,
    paddingVertical: 14, fontSize: 16, color: colors.text, marginBottom: spacing.md,
  },
  error: { color: '#FECACA', fontSize: 14, marginBottom: spacing.md, fontWeight: '600' },
  button: {
    backgroundColor: colors.accent, borderRadius: radius.md, paddingVertical: 16,
    alignItems: 'center', marginTop: spacing.sm,
  },
  buttonDisabled: { opacity: 0.7 },
  buttonText: { color: colors.primaryDark, fontSize: 16, fontWeight: '900' },
});
