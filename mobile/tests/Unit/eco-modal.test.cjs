const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');

test('modal bersama meneruskan Back Android, backdrop, dismiss picker dan safe area', () => {
  const module = { exports: {} };
  const source = ts.transpileModule(fs.readFileSync('src/components/eco-modal.tsx', 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX } }).outputText;
  const dependencies = {
    'react-native': { Modal: 'modal', Pressable: 'button', ScrollView: 'scroll', View: 'view', StyleSheet: { create: value => value } },
    'react-native-safe-area-context': { SafeAreaView: 'safe' },
  };
  vm.runInNewContext('(function(require,module,exports){' + source + '})')(name => dependencies[name] ?? require(name), module, module.exports);
  let closed = 0, dismissed = 0;
  const tree = module.exports.default({ visible: true, sheet: true, children: 'Kamera', onClose: () => { closed++; }, onDismiss: () => { dismissed++; } });
  assert.equal(tree.props.transparent, true); assert.equal(tree.props.visible, true);
  tree.props.onRequestClose(); assert.equal(closed, 1);
  tree.props.children.props.children[0].props.onPress(); assert.equal(closed, 2);
  tree.props.onDismiss(); assert.equal(dismissed, 1);
  const safe = tree.props.children.props.children[1]; assert.equal(safe.type, 'safe');
  assert.ok(safe.props.edges.includes('bottom')); assert.equal(safe.props.children.props.accessibilityViewIsModal, true);
});
