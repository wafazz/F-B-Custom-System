import React, { useEffect, useState } from 'react';
import { ActivityIndicator, StatusBar, View } from 'react-native';
import { NavigationContainer, DarkTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { hasSession } from '@/api/pos';
import LoginScreen from '@/screens/LoginScreen';
import QueueScreen from '@/screens/QueueScreen';
import SettingsScreen from '@/screens/SettingsScreen';
import type { RootStackParamList } from '@/navigation/types';

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function App() {
    const [initialRoute, setInitialRoute] = useState<keyof RootStackParamList | null>(null);

    useEffect(() => {
        void hasSession().then((ok) => setInitialRoute(ok ? 'Queue' : 'Login'));
    }, []);

    if (initialRoute === null) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#0f0f0f' }}>
                <StatusBar barStyle="light-content" backgroundColor="#0f0f0f" />
                <ActivityIndicator color="#f59e0b" />
            </View>
        );
    }

    return (
        <NavigationContainer theme={DarkTheme}>
            <StatusBar barStyle="light-content" backgroundColor="#0f0f0f" />
            <Stack.Navigator
                initialRouteName={initialRoute}
                screenOptions={{
                    headerStyle: { backgroundColor: '#171717' },
                    headerTintColor: '#fff7ed',
                    headerTitleStyle: { fontWeight: '800' },
                }}
            >
                <Stack.Screen
                    name="Login"
                    component={LoginScreen}
                    options={{ headerShown: false }}
                />
                <Stack.Screen
                    name="Queue"
                    component={QueueScreen}
                    options={{ title: 'Live queue', headerShown: false }}
                />
                <Stack.Screen
                    name="Settings"
                    component={SettingsScreen}
                    options={{ title: 'Settings' }}
                />
            </Stack.Navigator>
        </NavigationContainer>
    );
}
