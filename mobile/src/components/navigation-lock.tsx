import { createContext, useCallback, useContext, useRef, useState, type ReactNode } from 'react';

const NavigationLockContext = createContext<{
  locked: boolean;
  acquire: () => () => void;
  isLocked: () => boolean;
} | null>(null);

export function NavigationLockProvider({ children }: { children: ReactNode }) {
  const operations = useRef(new Set<symbol>());
  const [locked, setLocked] = useState(false);
  const isLocked = useCallback(() => operations.current.size > 0, []);
  const acquire = useCallback(() => {
    const operation = Symbol();
    operations.current.add(operation);
    setLocked(true);
    return () => {
      operations.current.delete(operation);
      setLocked(operations.current.size > 0);
    };
  }, []);
  return <NavigationLockContext.Provider value={{ locked, acquire, isLocked }}>{children}</NavigationLockContext.Provider>;
}

export function useNavigationLock() {
  const value = useContext(NavigationLockContext);
  if (!value) throw new Error('Navigasi membutuhkan NavigationLockProvider.');
  return value;
}
