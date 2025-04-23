import { User } from "."

export interface Access {
    id: string
    userEmail: string
    user?: User
    /**
     * This is polymorphic, so it can be a union, the only resource currently is
     * User, but maybe we can add others later
     */
    accessible?: User
    approvedAt?: string
}
