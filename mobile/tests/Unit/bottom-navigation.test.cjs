const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const React = require('react');

function load(file, dependencies = {}) {
  const module = { exports: {} };
  const source = ts.transpileModule(fs.readFileSync(file, 'utf8'), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX },
  }).outputText;
  vm.runInNewContext('(function(require,module,exports){' + source + '})')(
    name => dependencies[name] ?? require(name), module, module.exports,
  );
  return module.exports;
}

function nodes(node, result = []) {
  if (Array.isArray(node)) node.forEach(child => nodes(child, result));
  else if (node && typeof node === 'object' && node.props) {
    result.push(node);
    nodes(node.props.children, result);
  }
  return result;
}

function navigation({ path = '/', locked = false, keyboard = false } = {}) {
  const calls = [], listeners = {}, removed = [];
  let visible = keyboard, blocking = locked, effect;
  const component = load('src/components/bottom-navigation.tsx', {
    react: { useState: () => [visible, value => { visible = value; }], useEffect: callback => { effect = callback; } },
    'react-native': { View: 'view', Text: 'text', Pressable: 'tab', Platform: { OS: 'android' }, StyleSheet: { create: styles => styles }, Keyboard: {
      isVisible: () => visible,
      addListener: (name, callback) => { listeners[name] = callback; return { remove: () => removed.push(name) }; },
    } },
    'expo-router': { usePathname: () => path, router: { navigate: href => calls.push(href) } },
    'expo-symbols': { SymbolView: 'symbol' },
    'react-native-safe-area-context': { useSafeAreaInsets: () => ({ left: 0, right: 0, bottom: 24 }) },
    './navigation-lock': { useNavigationLock: () => ({ locked, isLocked: () => blocking }) },
  });
  return { ...component, calls, listeners, removed, block: () => { blocking = true; }, render: () => component.default(), subscribe: () => effect() };
}

test('navbar memiliki tepat empat menu dan active route mengikuti path termasuk detail', () => {
  const view = navigation();
  assert.deepEqual(Array.from(view.navigationItems, item => [item.href, item.label]), [
    ['/', 'Beranda'], ['/reports', 'Riwayat'], ['/create-report', 'Laporkan'], ['/profile', 'Profil'],
  ]);
  for (const path of ['/', '/reports', '/reports/42', '/create-report', '/profile', '/profile/settings']) {
    const active = path.startsWith('/reports') ? '/reports' : path.startsWith('/profile') ? '/profile' : path;
    const tabs = nodes(navigation({ path }).render()).filter(node => node.type === 'tab');
    assert.equal(tabs.length, 4);
    assert.equal(tabs.filter(node => node.props.accessibilityState.selected).length, 1);
    assert.equal(view.activeNavigationPath(path), active);
  }
  for (const path of ['/login', '/register', '/reports-other']) assert.equal(view.activeNavigationPath(path), undefined);
});

test('navigasi memakai router.navigate dan tab aktif tidak menumpuk route; lock langsung mencegah klik lama', () => {
  const view = navigation();
  const tabs = nodes(view.render()).filter(node => node.type === 'tab');
  tabs[0].props.onPress();
  tabs[1].props.onPress();
  assert.deepEqual(view.calls, ['/reports']);
  view.block();
  tabs[2].props.onPress();
  assert.deepEqual(view.calls, ['/reports']);
  assert.equal(navigation({ locked: true }).render(), null);
});

test('keyboard menyembunyikan navbar dan listener dilepas saat unmount', () => {
  const view = navigation();
  view.render();
  const cleanup = view.subscribe();
  view.listeners.keyboardDidShow();
  assert.equal(view.render(), null);
  view.listeners.keyboardDidHide();
  assert.equal(nodes(view.render()).filter(node => node.type === 'tab').length, 4);
  cleanup();
  assert.deepEqual(view.removed.sort(), ['keyboardDidHide', 'keyboardDidShow']);
});

test('layout bersama memasang satu navbar hanya untuk sesi authenticated dan mempertahankan child navigator', () => {
  const component = load('src/components/authenticated-layout.tsx', {
    'react-native': { View: 'view', StyleSheet: { create: styles => styles } },
    'react-native-safe-area-context': { useSafeAreaInsets: () => ({ bottom: 24 }) },
    './bottom-navigation': { __esModule: true, default: 'bottom-navigation' },
    './navigation-lock': { NavigationLockProvider: 'lock-provider' },
  }).default;
  for (const authenticated of [true, false]) {
    const tree = component({ authenticated, children: React.createElement('navigator') });
    const rendered = nodes(tree);
    assert.equal(rendered.filter(node => node.type === 'bottom-navigation').length, authenticated ? 1 : 0);
    assert.equal(rendered.filter(node => node.type === 'navigator').length, 1);
    assert.equal(tree.type, 'lock-provider');
  }
  for (const file of ['dashboard/screen', 'reporting/history', 'reporting/screen', 'auth/profile']) {
    const source = fs.readFileSync(`src/features/${file}.tsx`, 'utf8');
    assert.doesNotMatch(source, /AppBottomNav|<BottomNavigation|from ['"]\.\.\/dashboard\/screen/);
  }
  const root = fs.readFileSync('src/app/_layout.tsx', 'utf8');
  assert.match(root, /<AuthenticatedLayout authenticated=\{!!auth.user\}/);
  assert.equal((root.match(/<AuthenticatedLayout /g) ?? []).length, 1);
});

test('lock bersama menunggu seluruh operasi selesai dan release ganda tidak membuka operasi lain', () => {
  let locked = false;
  const component = load('src/components/navigation-lock.tsx', {
    react: { createContext: () => ({ Provider: 'provider' }), useCallback: callback => callback,
      useRef: value => ({ current: value }), useState: () => [locked, value => { locked = value; }] },
  });
  const value = component.NavigationLockProvider({ children: null }).props.value;
  const first = value.acquire(), second = value.acquire();
  assert.equal(locked, true);
  assert.equal(value.isLocked(), true);
  first(); first();
  assert.equal(locked, true);
  second();
  assert.equal(locked, false);
  assert.equal(value.isLocked(), false);
});
