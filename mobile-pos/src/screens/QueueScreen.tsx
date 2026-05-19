import React, { useCallback, useEffect, useState } from 'react';
import {
    Alert,
    FlatList,
    RefreshControl,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import {
    fetchQueue,
    getStoredBranch,
    logout,
    type PosBranch,
    type PosOrder,
    transitionOrder,
} from '@/api/pos';
import { printKitchenTicket } from '@/lib/printer';
import type { RootStackParamList } from '@/navigation/types';

type Props = NativeStackScreenProps<RootStackParamList, 'Queue'>;

const STATUS_COLOURS: Record<PosOrder['status'], string> = {
    pending: '#facc15',
    preparing: '#60a5fa',
    ready: '#34d399',
    completed: '#737373',
    cancelled: '#ef4444',
};

export default function QueueScreen({ navigation }: Props) {
    const [orders, setOrders] = useState<PosOrder[]>([]);
    const [branch, setBranch] = useState<PosBranch | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        const b = await getStoredBranch();
        if (!b) {
            navigation.replace('Login');
            return;
        }
        setBranch(b);
        try {
            const list = await fetchQueue(b.id);
            setOrders(list);
        } catch (e) {
            Alert.alert('Could not load queue', e instanceof Error ? e.message : String(e));
        }
    }, [navigation]);

    useEffect(() => {
        void load();
        const t = setInterval(() => void load(), 10_000);
        return () => clearInterval(t);
    }, [load]);

    async function handleAdvance(o: PosOrder) {
        const next: PosOrder['status'] =
            o.status === 'pending' ? 'preparing' : o.status === 'preparing' ? 'ready' : 'completed';
        try {
            await transitionOrder(o.id, next);
            void load();
        } catch (e) {
            Alert.alert('Could not advance', e instanceof Error ? e.message : String(e));
        }
    }

    async function handlePrint(o: PosOrder) {
        if (!branch) return;
        try {
            await printKitchenTicket({
                branchName: branch.name,
                orderNumber: o.number,
                orderType: o.order_type,
                table: o.dine_in_table,
                items: o.items,
            });
        } catch (e) {
            Alert.alert('Print failed', e instanceof Error ? e.message : String(e));
        }
    }

    async function handleLogout() {
        await logout();
        navigation.replace('Login');
    }

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.branchName}>{branch?.name ?? '—'}</Text>
                    <Text style={styles.branchCode}>{branch?.code}</Text>
                </View>
                <View style={styles.headerActions}>
                    <TouchableOpacity
                        style={styles.settingsButton}
                        onPress={() => navigation.navigate('Settings')}
                    >
                        <Text style={styles.settingsLabel}>Settings</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
                        <Text style={styles.logoutLabel}>Logout</Text>
                    </TouchableOpacity>
                </View>
            </View>

            <FlatList
                contentContainerStyle={{ padding: 12, paddingBottom: 64 }}
                data={orders}
                keyExtractor={(o) => String(o.id)}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={async () => { setRefreshing(true); await load(); setRefreshing(false); }} tintColor="#f59e0b" />}
                ListEmptyComponent={
                    <Text style={styles.empty}>No active orders. ☕</Text>
                }
                renderItem={({ item }) => (
                    <View style={styles.card}>
                        <View style={styles.cardHeader}>
                            <Text style={styles.orderNumber}>#{item.number}</Text>
                            <View
                                style={[styles.statusPill, { backgroundColor: STATUS_COLOURS[item.status] + '20' }]}
                            >
                                <View style={[styles.statusDot, { backgroundColor: STATUS_COLOURS[item.status] }]} />
                                <Text style={[styles.statusText, { color: STATUS_COLOURS[item.status] }]}>
                                    {item.status}
                                </Text>
                            </View>
                        </View>
                        <Text style={styles.orderType}>
                            {item.order_type}
                            {item.dine_in_table ? ` · Table ${item.dine_in_table}` : ''}
                        </Text>
                        {item.items.map((line, i) => (
                            <Text key={i} style={styles.itemLine}>
                                {line.quantity}x {line.name}
                                {line.modifiers.length > 0 ? `  +${line.modifiers.join(', +')}` : ''}
                            </Text>
                        ))}
                        <View style={styles.cardActions}>
                            {item.status !== 'completed' && item.status !== 'cancelled' && (
                                <TouchableOpacity
                                    style={[styles.actionButton, styles.primaryAction]}
                                    onPress={() => handleAdvance(item)}
                                >
                                    <Text style={styles.primaryActionText}>
                                        {item.status === 'pending'
                                            ? 'Start preparing'
                                            : item.status === 'preparing'
                                              ? 'Mark ready'
                                              : 'Mark completed'}
                                    </Text>
                                </TouchableOpacity>
                            )}
                            <TouchableOpacity
                                style={[styles.actionButton, styles.secondaryAction]}
                                onPress={() => handlePrint(item)}
                            >
                                <Text style={styles.secondaryActionText}>Print ticket</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                )}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0f0f0f' },
    header: {
        padding: 16,
        backgroundColor: '#171717',
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        borderBottomWidth: 1,
        borderBottomColor: '#262626',
    },
    branchName: { color: '#fff7ed', fontSize: 18, fontWeight: '800' },
    branchCode: { color: '#a3a3a3', fontSize: 12, marginTop: 2 },
    headerActions: { flexDirection: 'row', gap: 8 },
    settingsButton: { paddingHorizontal: 12, paddingVertical: 8, backgroundColor: '#262626', borderRadius: 999 },
    settingsLabel: { color: '#e5e5e5', fontWeight: '700', fontSize: 12 },
    logoutButton: { paddingHorizontal: 12, paddingVertical: 8, backgroundColor: '#7f1d1d', borderRadius: 999 },
    logoutLabel: { color: '#fecaca', fontWeight: '700', fontSize: 12 },
    empty: { color: '#a3a3a3', textAlign: 'center', padding: 64 },
    card: {
        backgroundColor: '#1a1a1a',
        borderRadius: 14,
        padding: 14,
        marginBottom: 10,
        borderWidth: 1,
        borderColor: '#262626',
    },
    cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    orderNumber: { color: '#fff', fontSize: 20, fontWeight: '800' },
    statusPill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999, gap: 6 },
    statusDot: { width: 8, height: 8, borderRadius: 4 },
    statusText: { fontWeight: '800', fontSize: 11, textTransform: 'uppercase' },
    orderType: { color: '#a3a3a3', marginTop: 4, marginBottom: 8, textTransform: 'capitalize' },
    itemLine: { color: '#e5e5e5', fontSize: 14, lineHeight: 22 },
    cardActions: { marginTop: 12, flexDirection: 'row', gap: 8 },
    actionButton: { flex: 1, paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
    primaryAction: { backgroundColor: '#f59e0b' },
    primaryActionText: { color: '#1c1410', fontWeight: '800' },
    secondaryAction: { backgroundColor: '#262626' },
    secondaryActionText: { color: '#fff7ed', fontWeight: '700' },
});
