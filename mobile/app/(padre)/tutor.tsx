import React from 'react'
import TutorIA from '../../components/TutorIA'
import { Colors } from '../../constants/Colors'

const SUGGESTIONS = [
  { emoji: '📈', label: 'Mejorar las notas', prompt: 'Mi hijo/a sacó notas bajas. ¿Cómo puedo ayudarle a mejorar desde casa? Dame estrategias concretas.' },
  { emoji: '🗓️', label: 'Rutina de estudio', prompt: '¿Cómo creo una rutina de estudio efectiva para un estudiante de secundaria? Dame un horario de ejemplo.' },
  { emoji: '🗣️', label: 'Reunión con docentes', prompt: '¿Qué preguntas importantes debo hacer en la reunión con el docente sobre el progreso de mi hijo/a?' },
  { emoji: '💛', label: 'Motivar sin presionar', prompt: 'Mi hijo/a dice que no entiende matemáticas y se frustra. ¿Cómo puedo motivarle sin que se sienta presionado/a?' },
]

export default function TutorPadre() {
  return (
    <TutorIA
      accentColor={Colors.roles.padre}
      avatarEmoji="🤝"
      title="Asistente IA"
      subtitle="Orientación para acompañar a tu hijo/a · ZuraAI"
      placeholder="Escribe tu consulta sobre el progreso de tu hijo/a…"
      suggestions={SUGGESTIONS}
    />
  )
}
