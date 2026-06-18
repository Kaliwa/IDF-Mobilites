import { describe, it, expect } from 'vitest'
import {
  calculer,
  calculerOptions,
  seuilRentabilite,
  euros,
  TARIFS,
  SEMAINES_PAR_MOIS,
} from '../tarifs'

describe('euros', () => {
  it('formate un montant en euros', () => {
    // Intl.NumberFormat fr-FR inserts a narrow no-break space ( ) before the currency symbol
    expect(euros(90.8)).toMatch(/90,80\s€/)
  })

  it('accepte un nombre de decimales personnalise', () => {
    expect(euros(1.5, 0)).toMatch(/2\s€/)
  })
})

describe('calculerOptions - profil standard', () => {
  it('inclut tickets, liberte, navigo-mois et navigo-annuel', () => {
    const options = calculerOptions('standard', 10, false, 'reduction50')
    const ids = options.map((o) => o.id)
    expect(ids).toContain('tickets')
    expect(ids).toContain('liberte')
    expect(ids).toContain('navigo-mois')
    expect(ids).toContain('navigo-annuel')
    expect(ids).not.toContain('imagine-r')
  })

  it('divise le cout par deux avec prise en charge employeur', () => {
    const sans = calculerOptions('standard', 10, false, 'reduction50')
    const avec = calculerOptions('standard', 10, true, 'reduction50')
    const navigo = (id: string, opts: typeof sans) =>
      opts.find((o) => o.id === id)!.coutMensuel
    expect(navigo('navigo-mois', avec)).toBeCloseTo(navigo('navigo-mois', sans) / 2)
  })
})

describe('calculerOptions - profil etudiant', () => {
  it('inclut Imagine R Etudiant', () => {
    const ids = calculerOptions('etudiant', 5, false, 'reduction50').map((o) => o.id)
    expect(ids).toContain('imagine-r')
  })
})

describe('calculerOptions - profil TST', () => {
  it('inclut navigo-tst avec reduction 50%', () => {
    const options = calculerOptions('tst', 10, false, 'reduction50')
    const tst = options.find((o) => o.id === 'navigo-tst')!
    expect(tst).toBeDefined()
    expect(tst.coutMensuel).toBeCloseTo(TARIFS.navigoMois * 0.5)
  })

  it('cout nul pour la gratuite TST', () => {
    const options = calculerOptions('tst', 10, false, 'gratuite')
    expect(options.find((o) => o.id === 'navigo-tst')!.coutMensuel).toBe(0)
  })
})

describe('seuilRentabilite', () => {
  it('retourne un seuil positif pour un profil standard', () => {
    const seuil = seuilRentabilite('standard', false, 'reduction50')
    expect(seuil).not.toBeNull()
    expect(seuil!).toBeGreaterThan(0)
  })
})

describe('calculer', () => {
  it("la recommandation est l'option la moins chere", () => {
    const res = calculer('standard', 20, false, 'reduction50')
    const minCout = Math.min(...res.options.map((o) => o.coutMensuel))
    expect(res.reco.coutMensuel).toBeCloseTo(minCout)
  })

  it("l'economie mensuelle est positive si la reco est moins chere que la baseline", () => {
    const res = calculer('standard', 20, false, 'reduction50')
    expect(res.economieMois).toBeGreaterThanOrEqual(0)
    expect(res.economieAn).toBeCloseTo(res.economieMois * 12)
  })

  it('les trajets en ticket coutent trajetsSemaine * SEMAINES_PAR_MOIS * 2.55', () => {
    const trajetsSemaine = 10
    const res = calculer('standard', trajetsSemaine, false, 'reduction50')
    const tickets = res.options.find((o) => o.id === 'tickets')!
    expect(tickets.coutMensuel).toBeCloseTo(
      trajetsSemaine * SEMAINES_PAR_MOIS * TARIFS.ticketMetro,
    )
  })
})
