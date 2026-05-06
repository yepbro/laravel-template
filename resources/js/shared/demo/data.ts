export interface DemoLead {
    id: number;
    name: string;
    email: string;
    company: string;
    mode: string;
    status: 'New' | 'Trial' | 'Qualified';
}

export interface DemoLeadInput {
    name: string;
    email: string;
    company: string;
    mode: string;
}

export const starterLeads: DemoLead[] = [
    {
        id: 1,
        name: 'Maya Chen',
        email: 'maya@northstar.dev',
        company: 'Northstar',
        mode: 'Vue-only SPA',
        status: 'Qualified',
    },
    {
        id: 2,
        name: 'Jonas Reed',
        email: 'jonas@lattice.io',
        company: 'Lattice',
        mode: 'Vue-only SPA',
        status: 'Trial',
    },
    {
        id: 3,
        name: 'Amina Patel',
        email: 'amina@orbit.app',
        company: 'Orbit',
        mode: 'Vue-only SPA',
        status: 'New',
    },
];
