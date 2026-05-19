import React, { useEffect, useState } from 'react';
import {
    Alert,
    FlatList,
    PermissionsAndroid,
    Platform,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';
import {
    getSelectedPrinter,
    listPairedDevices,
    selectPrinter,
    type PairedDevice,
    printText,
} from '@/lib/printer';

async function ensureBluetoothPermissions(): Promise<boolean> {
    if (Platform.OS !== 'android') return true;
    const sdk = Platform.Version as number;
    if (sdk >= 31) {
        const res = await PermissionsAndroid.requestMultiple([
            PermissionsAndroid.PERMISSIONS.BLUETOOTH_CONNECT,
            PermissionsAndroid.PERMISSIONS.BLUETOOTH_SCAN,
        ]);
        return Object.values(res).every((v) => v === PermissionsAndroid.RESULTS.GRANTED);
    }
    return true;
}

export default function SettingsScreen() {
    const [devices, setDevices] = useState<PairedDevice[]>([]);
    const [current, setCurrent] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function load() {
        setLoading(true);
        try {
            const ok = await ensureBluetoothPermissions();
            if (!ok) {
                Alert.alert('Permission needed', 'Bluetooth permissions are required to scan paired printers.');
                return;
            }
            const list = await listPairedDevices();
            setDevices(list);
            setCurrent(await getSelectedPrinter());
        } catch (e) {
            Alert.alert('Could not load devices', e instanceof Error ? e.message : String(e));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void load();
    }, []);

    async function pick(d: PairedDevice) {
        await selectPrinter(d.address);
        setCurrent(d.address);
    }

    async function testPrint() {
        try {
            await printText(
                'STAR COFFEE\n  Test print\n--------------------------------\nIf you can read this,\nthe printer is wired up.\n',
            );
            Alert.alert('Sent', 'Check the printer.');
        } catch (e) {
            Alert.alert('Test failed', e instanceof Error ? e.message : String(e));
        }
    }

    return (
        <View style={styles.container}>
            <Text style={styles.heading}>Bluetooth printer</Text>
            <Text style={styles.note}>
                Pair the printer in Android Settings first, then pick it here.
            </Text>

            <FlatList
                data={devices}
                keyExtractor={(d) => d.address}
                refreshing={loading}
                onRefresh={load}
                ListEmptyComponent={
                    <Text style={styles.empty}>
                        No paired Bluetooth devices found.
                    </Text>
                }
                renderItem={({ item }) => {
                    const selected = item.address === current;
                    return (
                        <TouchableOpacity
                            onPress={() => pick(item)}
                            style={[styles.device, selected && styles.deviceSelected]}
                        >
                            <View style={{ flex: 1 }}>
                                <Text style={styles.deviceName}>{item.name}</Text>
                                <Text style={styles.deviceAddr}>{item.address}</Text>
                            </View>
                            {selected && <Text style={styles.selectedTag}>SELECTED</Text>}
                        </TouchableOpacity>
                    );
                }}
            />

            {current && (
                <TouchableOpacity style={styles.testButton} onPress={testPrint}>
                    <Text style={styles.testLabel}>Send test print</Text>
                </TouchableOpacity>
            )}
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0f0f0f', padding: 16 },
    heading: { color: '#fff7ed', fontSize: 22, fontWeight: '800' },
    note: { color: '#a3a3a3', marginTop: 4, marginBottom: 16 },
    empty: { color: '#a3a3a3', textAlign: 'center', padding: 32 },
    device: {
        backgroundColor: '#1a1a1a',
        borderRadius: 12,
        padding: 14,
        marginBottom: 8,
        flexDirection: 'row',
        alignItems: 'center',
        borderWidth: 1,
        borderColor: '#262626',
    },
    deviceSelected: { borderColor: '#f59e0b', backgroundColor: '#3b2a0e' },
    deviceName: { color: '#fff', fontSize: 16, fontWeight: '700' },
    deviceAddr: { color: '#a3a3a3', fontSize: 12 },
    selectedTag: { color: '#f59e0b', fontWeight: '800', fontSize: 11 },
    testButton: {
        backgroundColor: '#f59e0b',
        padding: 14,
        borderRadius: 12,
        alignItems: 'center',
        marginTop: 12,
    },
    testLabel: { color: '#1c1410', fontWeight: '800' },
});
