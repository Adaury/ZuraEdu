import React from 'react'
import TutorIA from '../../components/TutorIA'
import { Colors } from '../../constants/Colors'

const SUGGESTIONS = [
  { emoji: '📐', label: 'Resolver ecuaciones', prompt: 'Explícame paso a paso cómo resolver ecuaciones de primer grado con ejemplos.' },
  { emoji: '✍️', label: 'Escribir un ensayo', prompt: '¿Cómo estructuro correctamente un ensayo académico? Dame esquema y ejemplo.' },
  { emoji: '📅', label: 'Plan de estudio', prompt: 'Crea un plan de estudio de 5 días para prepararme para un examen de Ciencias.' },
  { emoji: '🏛️', label: 'Historia Dominicana', prompt: 'Explícame las causas y consecuencias de la Independencia Dominicana.' },
]

export default function TutorEstudiante() {
  return (
    <TutorIA
      accentColor={Colors.roles.estudiante}
      avatarEmoji="🤖"
      title="Tutor IA"
      subtitle="Tu asistente académico personal · ZuraAI"
      placeholder="Escribe tu pregunta o duda…"
      suggestions={SUGGESTIONS}
    />
  )
}
