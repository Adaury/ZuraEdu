import React, { useState, useRef, useCallback } from 'react'
import {
  View, Text, TextInput, TouchableOpacity, FlatList,
  StyleSheet, KeyboardAvoidingView, Platform, ActivityIndicator,
  Keyboard,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { Ionicons } from '@expo/vector-icons'
import { tutorApi } from '../services/api'
import { Colors } from '../constants/Colors'

type Message = { id: string; role: 'user' | 'assistant'; text: string }
type Suggestion = { emoji: string; label: string; prompt: string }

interface Props {
  accentColor: string
  accentGradientEnd?: string
  avatarEmoji: string
  title: string
  subtitle: string
  placeholder: string
  suggestions: Suggestion[]
}

export default function TutorIA({
  accentColor,
  accentGradientEnd,
  avatarEmoji,
  title,
  subtitle,
  placeholder,
  suggestions,
}: Props) {
  const [messages, setMessages]   = useState<Message[]>([])
  const [input, setInput]         = useState('')
  const [loading, setLoading]     = useState(false)
  const listRef                   = useRef<FlatList>(null)
  const historyRef                = useRef<{ role: 'user' | 'assistant'; content: string }[]>([])
  const accent                    = accentColor

  const send = useCallback(async (text: string) => {
    const trimmed = text.trim()
    if (!trimmed || loading) return
    setInput('')
    Keyboard.dismiss()

    const userMsg: Message = { id: Date.now().toString(), role: 'user', text: trimmed }
    setMessages(prev => [...prev, userMsg])
    setLoading(true)

    try {
      const res = await tutorApi.chat(trimmed, historyRef.current)
      const reply: string = res.data?.response ?? 'Sin respuesta del tutor.'

      const botMsg: Message = { id: (Date.now() + 1).toString(), role: 'assistant', text: reply }
      setMessages(prev => [...prev, botMsg])

      historyRef.current = [
        ...historyRef.current,
        { role: 'user' as const, content: trimmed },
        { role: 'assistant' as const, content: reply },
      ].slice(-20)
    } catch (err: any) {
      const errText = err.response?.data?.error ?? 'Error al conectar con el Tutor IA.'
      setMessages(prev => [...prev, { id: Date.now().toString(), role: 'assistant', text: `⚠️ ${errText}` }])
    } finally {
      setLoading(false)
      setTimeout(() => listRef.current?.scrollToEnd({ animated: true }), 100)
    }
  }, [loading])

  const reset = () => {
    setMessages([])
    historyRef.current = []
    setInput('')
  }

  const renderMessage = ({ item }: { item: Message }) => {
    const isUser = item.role === 'user'
    return (
      <View style={[styles.msgRow, isUser && styles.msgRowUser]}>
        {!isUser && (
          <View style={[styles.avatar, { backgroundColor: accent }]}>
            <Text style={styles.avatarEmoji}>{avatarEmoji}</Text>
          </View>
        )}
        <View style={[
          styles.bubble,
          isUser ? [styles.bubbleUser, { backgroundColor: accent }] : styles.bubbleBot,
          { maxWidth: '78%' },
        ]}>
          <Text style={[styles.bubbleText, isUser && styles.bubbleTextUser]}>
            {item.text}
          </Text>
        </View>
        {isUser && (
          <View style={[styles.avatar, styles.avatarUser]}>
            <Ionicons name="person" size={16} color={Colors.muted} />
          </View>
        )}
      </View>
    )
  }

  const showWelcome = messages.length === 0

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
      >
        {/* Header */}
        <View style={[styles.header, { borderBottomColor: accent + '30' }]}>
          <View style={[styles.headerIcon, { backgroundColor: accent }]}>
            <Text style={{ fontSize: 18 }}>{avatarEmoji}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.headerTitle}>{title}</Text>
            <Text style={styles.headerSub}>{subtitle}</Text>
          </View>
          {messages.length > 0 && (
            <TouchableOpacity onPress={reset} style={styles.resetBtn}>
              <Ionicons name="refresh" size={18} color={Colors.muted} />
            </TouchableOpacity>
          )}
        </View>

        {/* Lista de mensajes / bienvenida */}
        {showWelcome ? (
          <View style={styles.welcomeWrap}>
            <View style={[styles.welcomeIcon, { backgroundColor: accent + '18' }]}>
              <Text style={{ fontSize: 44 }}>{avatarEmoji}</Text>
            </View>
            <Text style={[styles.welcomeTitle, { color: accent }]}>{title}</Text>
            <Text style={styles.welcomeSub}>{subtitle}</Text>

            <View style={styles.suggestionsGrid}>
              {suggestions.map((s, i) => (
                <TouchableOpacity
                  key={i}
                  style={[styles.suggCard, { borderColor: accent + '30' }]}
                  onPress={() => send(s.prompt)}
                  activeOpacity={0.8}
                >
                  <Text style={styles.suggEmoji}>{s.emoji}</Text>
                  <Text style={styles.suggLabel}>{s.label}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        ) : (
          <FlatList
            ref={listRef}
            data={messages}
            keyExtractor={item => item.id}
            renderItem={renderMessage}
            contentContainerStyle={styles.msgList}
            onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: true })}
          />
        )}

        {/* Indicador de carga */}
        {loading && (
          <View style={styles.loadingRow}>
            <View style={[styles.avatar, { backgroundColor: accent }]}>
              <Text style={styles.avatarEmoji}>{avatarEmoji}</Text>
            </View>
            <View style={styles.typingBubble}>
              <ActivityIndicator size="small" color={accent} />
              <Text style={[styles.typingText, { color: accent }]}>Pensando…</Text>
            </View>
          </View>
        )}

        {/* Input */}
        <View style={[styles.inputWrap, { borderTopColor: accent + '20' }]}>
          <TextInput
            style={styles.input}
            value={input}
            onChangeText={setInput}
            placeholder={placeholder}
            placeholderTextColor={Colors.muted}
            multiline
            maxLength={2000}
            returnKeyType="send"
            onSubmitEditing={() => send(input)}
          />
          <TouchableOpacity
            style={[styles.sendBtn, { backgroundColor: accent }, (!input.trim() || loading) && styles.sendBtnDisabled]}
            onPress={() => send(input)}
            disabled={!input.trim() || loading}
            activeOpacity={0.85}
          >
            <Ionicons name="send" size={18} color="#fff" />
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  flex:            { flex: 1 },
  header:          { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14, backgroundColor: '#fff', borderBottomWidth: 1 },
  headerIcon:      { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center' },
  headerTitle:     { fontSize: 15, fontWeight: '800', color: Colors.text },
  headerSub:       { fontSize: 11, color: Colors.muted },
  resetBtn:        { padding: 8 },
  // Welcome
  welcomeWrap:     { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 20, gap: 16 },
  welcomeIcon:     { width: 88, height: 88, borderRadius: 44, alignItems: 'center', justifyContent: 'center', marginBottom: 4 },
  welcomeTitle:    { fontSize: 20, fontWeight: '900' },
  welcomeSub:      { fontSize: 13, color: Colors.muted, textAlign: 'center', lineHeight: 20 },
  suggestionsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'center', marginTop: 8 },
  suggCard:        { backgroundColor: '#fff', borderRadius: 14, padding: 14, alignItems: 'center', gap: 6, borderWidth: 1.5, width: '46%', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  suggEmoji:       { fontSize: 24 },
  suggLabel:       { fontSize: 11, fontWeight: '700', color: Colors.text, textAlign: 'center' },
  // Messages
  msgList:         { padding: 16, gap: 14, paddingBottom: 8 },
  msgRow:          { flexDirection: 'row', alignItems: 'flex-end', gap: 8 },
  msgRowUser:      { flexDirection: 'row-reverse' },
  avatar:          { width: 32, height: 32, borderRadius: 16, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  avatarUser:      { backgroundColor: '#f1f5f9' },
  avatarEmoji:     { fontSize: 16 },
  bubble:          { borderRadius: 18, padding: 12 },
  bubbleBot:       { backgroundColor: '#fff', shadowColor: '#000', shadowOpacity: .05, shadowRadius: 5, elevation: 2 },
  bubbleUser:      { borderBottomRightRadius: 4 },
  bubbleText:      { fontSize: 14, color: Colors.text, lineHeight: 22 },
  bubbleTextUser:  { color: '#fff' },
  // Loading
  loadingRow:      { flexDirection: 'row', alignItems: 'flex-end', gap: 8, paddingHorizontal: 16, paddingBottom: 6 },
  typingBubble:    { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#fff', borderRadius: 18, padding: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 4, elevation: 2 },
  typingText:      { fontSize: 12, fontWeight: '600' },
  // Input
  inputWrap:       { flexDirection: 'row', alignItems: 'flex-end', gap: 10, padding: 12, backgroundColor: '#fff', borderTopWidth: 1 },
  input:           { flex: 1, fontSize: 14, color: Colors.text, maxHeight: 120, backgroundColor: Colors.bg, borderRadius: 16, paddingHorizontal: 14, paddingVertical: 10, lineHeight: 20 },
  sendBtn:         { width: 42, height: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center', shadowColor: '#000', shadowOpacity: .15, shadowRadius: 6, elevation: 3 },
  sendBtnDisabled: { opacity: 0.45 },
})
