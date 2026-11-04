import AuthenticatedLayout from '../components/authenticated-layout';
import { Stack } from 'expo-router';
import { ActivityIndicator, Text } from 'react-native';
import { AuthProvider,useAuth } from '../features/auth/provider';
import { Action,AuthShell,styles } from '../features/auth/screen';
function Routes() {
 const auth=useAuth();
 if(auth.status!=='ready')return <AuthShell>{auth.status==='loading'?<ActivityIndicator accessibilityLabel="Memulihkan sesi" color="#006c49"/>:<><Text style={styles.error}>Sesi belum dapat diperiksa. Periksa koneksi dan coba lagi.</Text><Action label="Coba lagi" onPress={()=>void auth.restore()}/></>}</AuthShell>;
 return <AuthenticatedLayout authenticated={!!auth.user}><Stack screenOptions={{headerShown:false}}><Stack.Protected guard={!!auth.user}><Stack.Screen name="index"/><Stack.Screen name="reports"/><Stack.Screen name="create-report"/><Stack.Screen name="profile"/></Stack.Protected><Stack.Protected guard={!auth.user}><Stack.Screen name="login"/><Stack.Screen name="register"/></Stack.Protected></Stack></AuthenticatedLayout>;
}
export default function Layout(){return <AuthProvider><Routes/></AuthProvider>;}
