import React, { useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { login } from '@/api/pos';
import type { RootStackParamList } from '@/navigation/types';

type Props = NativeStackScreenProps<RootStackParamList, 'Login'>;

export default function LoginScreen({ navigation }: Props) {
    const [branchCode, setBranchCode] = useState('');
    const [pin, setPin] = useState('');
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit() {
        if (!branchCode.trim() || !pin.trim()) {
            Alert.alert('Missing info', 'Branch code and PIN are required.');
            return;
        }
        setSubmitting(true);
        try {
            await login(branchCode.trim().toUpperCase(), pin.trim());
            navigation.replace('Queue');
        } catch (e) {
            Alert.alert('Sign-in failed', e instanceof Error ? e.message : String(e));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <View style={styles.container}>
            <Text style={styles.title}>Star Coffee POS</Text>
            <Text style={styles.subtitle}>Sign in with branch + PIN</Text>

            <Text style={styles.label}>Branch code</Text>
            <TextInput
                value={branchCode}
                onChangeText={setBranchCode}
                placeholder="STAR-HQ"
                autoCapitalize="characters"
                style={styles.input}
            />

            <Text style={styles.label}>Staff PIN</Text>
            <TextInput
                value={pin}
                onChangeText={setPin}
                placeholder="••••"
                keyboardType="numeric"
                secureTextEntry
                style={styles.input}
            />

            <TouchableOpacity
                style={[styles.button, submitting && styles.buttonDisabled]}
                onPress={handleSubmit}
                disabled={submitting}
            >
                {submitting ? (
                    <ActivityIndicator color="#fff" />
                ) : (
                    <Text style={styles.buttonText}>Sign in</Text>
                )}
            </TouchableOpacity>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, padding: 24, backgroundColor: '#0f0f0f', justifyContent: 'center' },
    title: { color: '#fff7ed', fontSize: 32, fontWeight: '800', textAlign: 'center' },
    subtitle: { color: '#a3a3a3', textAlign: 'center', marginBottom: 32 },
    label: { color: '#d4d4d4', marginTop: 16, marginBottom: 6, fontWeight: '600' },
    input: {
        backgroundColor: '#1f1f1f',
        borderRadius: 8,
        padding: 14,
        color: '#fff',
        fontSize: 16,
    },
    button: {
        backgroundColor: '#f59e0b',
        marginTop: 28,
        padding: 16,
        borderRadius: 12,
        alignItems: 'center',
    },
    buttonDisabled: { opacity: 0.5 },
    buttonText: { color: '#1c1410', fontWeight: '800', fontSize: 16 },
});
