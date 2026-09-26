import { describe,expect,test } from 'vitest';
import { EDITOR_VIEWPORT_ACCEPTANCE } from './pages/CmsPublicPage';

const luminance=(hex:string)=>{const channels=hex.match(/[a-f\d]{2}/gi)!.map(value=>parseInt(value,16)/255).map(value=>value<=.04045?value/12.92:Math.pow((value+.055)/1.055,2.4));return .2126*channels[0]+.7152*channels[1]+.0722*channels[2]};
const contrast=(foreground:string,background:string)=>{const a=luminance(foreground),b=luminance(background);return (Math.max(a,b)+.05)/(Math.min(a,b)+.05)};

describe('shared accessibility contract',()=>{
 test('semantic text colours meet WCAG AA against their normal backgrounds',()=>{
  expect(contrast('aa4d69','ffffff')).toBeGreaterThanOrEqual(4.5);
  expect(contrast('74686e','ffffff')).toBeGreaterThanOrEqual(4.5);
  expect(contrast('d8c8ce','481025')).toBeGreaterThanOrEqual(4.5);
 expect(contrast('9e8a92','25171d')).toBeGreaterThanOrEqual(4.5);
 });
 test('editor overlays define reflow safeguards for every acceptance viewport',()=>{
  expect(EDITOR_VIEWPORT_ACCEPTANCE.widths).toEqual([320,390,768,1024,1440]);
  expect(EDITOR_VIEWPORT_ACCEPTANCE.mobileMax).toBe(650);
  expect(EDITOR_VIEWPORT_ACCEPTANCE.tabletMax).toBe(1024);
 });
});
