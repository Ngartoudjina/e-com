<template>
  <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 text-center mb-8">
      FAQ
    </h1>
    <div class="max-w-3xl mx-auto">
      <Accordion class="w-full">
        <AccordionItem v-for="item in faqData" :key="item.id" :value="item.id">
          <AccordionTrigger class="text-lg font-medium hover:bg-gray-50 transition-colors duration-200">
            {{ item.question }}
          </AccordionTrigger>
          <AccordionContent class="text-gray-600 leading-relaxed">
            {{ item.answer }}
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/components/ui/index'

interface FaqItem {
  id: string
  question: string
  answer: string
}

const faqData: FaqItem[] = [
  {
    id: '1',
    question: 'Comment passer une commande ?',
    answer: 'Pour passer une commande, sélectionnez vos produits, ajoutez-les au panier, puis cliquez sur \'Passer la commande\'. Suivez les étapes pour finaliser le paiement.',
  },
  {
    id: '2',
    question: 'Quels sont les délais de livraison ?',
    answer: 'Les délais de livraison varient entre 2 et 5 jours ouvrables selon votre localisation. Vous recevrez un email de confirmation avec les détails.',
  },
  {
    id: '3',
    question: 'Comment contacter le support ?',
    answer: 'Vous pouvez nous contacter via notre formulaire de contact ou par email à support@e-com.com. Nous répondons sous 24h.',
  },
]
</script>
