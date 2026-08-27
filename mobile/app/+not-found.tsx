import { Link } from 'expo-router';import { Heading,Screen,text } from '@/components/ui';
export default function NotFound(){return <Screen><Heading title="Page not found" body="This link is not available in the patient app."/><Link href="/(tabs)" style={text.link}>Return home</Link></Screen>}
