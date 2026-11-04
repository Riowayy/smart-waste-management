import { createContext, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import { session, type Credentials, type User } from './session';
function useSession() {
 const [user,setUser] = useState<User | null>(null);
 const [status,setStatus] = useState<'loading'|'ready'|'error'>('loading');
 const busy = useRef(false);
 async function restore() {
  if (busy.current) return;
  busy.current = true; setStatus('loading');
  try { setUser(await session.restore()); setStatus('ready'); }
  catch { setStatus('error'); }
  finally { busy.current = false; }
 }
 useEffect(() => { void restore(); }, []);
 async function authenticate(mode: 'login'|'register', values: Credentials) {
  if (busy.current) return;
  busy.current = true;
  try { setUser(await session.authenticate(mode, values)); }
  finally { busy.current = false; }
 }
 async function logout() {
  if (busy.current) return;
  busy.current = true;
  try { await session.logout(); setUser(null); }
  finally { busy.current = false; }
 }
 return { user,status,restore,authenticate,logout };
}
const Context = createContext<ReturnType<typeof useSession> | null>(null);
export function AuthProvider({children}: {children: ReactNode}) {
 const auth = useSession();
 return <Context.Provider value={auth}>{children}</Context.Provider>;
}
export function useAuth() {
 const auth = useContext(Context);
 if (!auth) throw new Error('useAuth membutuhkan AuthProvider.');
 return auth;
}
