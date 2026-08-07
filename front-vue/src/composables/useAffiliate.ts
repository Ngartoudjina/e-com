import { ref } from 'vue'

export interface AffiliateData {
  affiliateCode: string
  referralLink: string
  totalEarnings: number
  totalReferrals: number
}

export function useAffiliate() {
  const affiliateData = ref<AffiliateData | null>(null)
  const loading = ref(false)

  const becomeAffiliate = async (uid: string) => {
    loading.value = true
    try {
      const response = await fetch('/api/affiliate/become-affiliate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ uid }),
      })

      const data = await response.json()

      if (data.success) {
        affiliateData.value = data.affiliate
        return data.affiliate
      }
    } catch (error) {
      console.error('Erreur lors de la création de l\'affiliation:', error)
    } finally {
      loading.value = false
    }
  }

  const trackAffiliateClick = async (affiliateCode: string) => {
    const visitorId = localStorage.getItem('visitorId') || generateVisitorId()
    localStorage.setItem('visitorId', visitorId)
    localStorage.setItem('affiliateCode', affiliateCode)

    try {
      await fetch('/api/affiliate/track-click', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ affiliateCode, visitorId }),
      })
    } catch (error) {
      console.error('Erreur lors du tracking:', error)
    }
  }

  const checkDiscount = async (userId: string, orderTotal: number) => {
    try {
      const response = await fetch('/api/affiliate/apply-discount', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId, orderTotal }),
      })

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la vérification de la réduction:', error)
      return { hasDiscount: false }
    }
  }

  return {
    affiliateData,
    loading,
    becomeAffiliate,
    trackAffiliateClick,
    checkDiscount,
  }
}

function generateVisitorId() {
  return Math.random().toString(36).substring(2, 11)
}
